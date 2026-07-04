<?php

namespace App\Services;

use App\Models\RecitationPlan;
use App\Models\RecitationSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecitationPlannerService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createPlan(int $userId, array $data): RecitationPlan
    {
        return DB::transaction(function () use ($userId, $data): RecitationPlan {
            return RecitationPlan::query()->create([
                'user_id' => $userId,
                'title' => $data['title'] ?? null,
                'period_type' => $data['period_type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $data['status'] ?? 'draft',
            ]);
        });
    }

    /**
     * يدمج عدة خطط تلاوة متتالية (متلاصقة زمنياً) في خطة واحدة تغطي كامل المدى.
     *
     * الخطط يجب أن تكون متلاصقة تماماً: نهاية كل خطة + يوم = بداية الخطة التالية
     * (بلا فجوة ولا تداخل). عند deleteSources تُنقل الجلسات (بمقاطعها وأرشيفها) إلى
     * الخطة الجديدة ثم تُحذف الخطط المصدر؛ وإلا تُنسخ الجلسات والمقاطع دون المساس بالأصل.
     *
     * @param  array<int, int>  $planIds
     */
    public function mergePlans(int $userId, array $planIds, ?string $title, bool $deleteSources): RecitationPlan
    {
        $ids = array_values(array_unique(array_map('intval', $planIds)));

        $plans = RecitationPlan::query()
            ->whereIn('id', $ids)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        if ($plans->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'plan_ids' => 'تعذر العثور على بعض الخطط المحددة.',
            ]);
        }

        foreach ($plans as $plan) {
            abort_if((int) $plan->user_id !== $userId, 403);
        }

        // التحقق من التلاصق: نهاية كل خطة + يوم = بداية التي تليها.
        $ordered = $plans->values();
        for ($i = 1; $i < $ordered->count(); $i++) {
            $prevEnd = Carbon::parse($ordered[$i - 1]->end_date)->toDateString();
            $expectedStart = Carbon::parse($prevEnd)->addDay()->toDateString();
            $currentStart = Carbon::parse($ordered[$i]->start_date)->toDateString();

            if ($currentStart !== $expectedStart) {
                throw ValidationException::withMessages([
                    'plan_ids' => 'الخطط المحددة غير متتالية (متصلة). يجب أن تكون متلاصقة زمنياً بلا فجوة ولا تداخل.',
                ]);
            }
        }

        $startDate = Carbon::parse($ordered->first()->start_date)->toDateString();
        $endDate = Carbon::parse($ordered->last()->end_date)->toDateString();
        $spanDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $periodType = $spanDays > 7 ? 'monthly' : 'weekly';
        $mergedTitle = $title !== null && trim($title) !== ''
            ? trim($title)
            : ($ordered->first()->title ?? null);

        return DB::transaction(function () use (
            $userId,
            $ordered,
            $ids,
            $deleteSources,
            $mergedTitle,
            $periodType,
            $startDate,
            $endDate
        ): RecitationPlan {
            $merged = RecitationPlan::query()->create([
                'user_id' => $userId,
                'title' => $mergedTitle,
                'period_type' => $periodType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
            ]);

            if ($deleteSources) {
                // نقل الجلسات (ومعها المقاطع وسجلات الأرشيف المرتبطة بـ session_id) كما هي.
                // التلاصق يضمن عدم تعارض (date, prayer_name) داخل الخطة الواحدة.
                RecitationSession::query()
                    ->whereIn('plan_id', $ids)
                    ->where('user_id', $userId)
                    ->update(['plan_id' => $merged->id]);

                // الخطط المصدر لم يعد لها جلسات؛ حذفها لا يمسّ الجلسات المنقولة ولا أرشيفها.
                RecitationPlan::query()
                    ->whereIn('id', $ids)
                    ->where('user_id', $userId)
                    ->delete();
            } else {
                foreach ($ordered as $plan) {
                    $sessions = RecitationSession::query()
                        ->where('plan_id', $plan->id)
                        ->where('user_id', $userId)
                        ->with('segments')
                        ->get();

                    foreach ($sessions as $session) {
                        $copy = $merged->sessions()->create([
                            'user_id' => $userId,
                            'date' => Carbon::parse($session->date)->toDateString(),
                            'day_of_week' => $session->day_of_week,
                            'prayer_name' => $session->prayer_name,
                            'execution_status' => $session->execution_status,
                        ]);

                        foreach ($session->segments as $segment) {
                            $copy->segments()->create([
                                'rakaa_number' => $segment->rakaa_number,
                                'start_surah' => $segment->start_surah,
                                'start_ayah' => $segment->start_ayah,
                                'end_surah' => $segment->end_surah,
                                'end_ayah' => $segment->end_ayah,
                                'order_index' => $segment->order_index,
                            ]);
                        }
                    }
                }
            }

            return $merged;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $sessions
     */
    public function upsertSessions(RecitationPlan $plan, int $userId, array $sessions): array
    {
        $result = [];

        DB::transaction(function () use ($plan, $userId, $sessions, &$result): void {
            foreach ($sessions as $item) {
                $date = Carbon::parse($item['date'])->toDateString();
                $dayOfWeek = (int) Carbon::parse($date)->dayOfWeekIso; // 1=Mon ... 7=Sun

                $session = RecitationSession::query()->updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'user_id' => $userId,
                        'date' => $date,
                        'prayer_name' => $item['prayer_name'],
                    ],
                    [
                        'day_of_week' => $dayOfWeek,
                        'execution_status' => $item['execution_status'] ?? RecitationCompletionService::STATUS_SCHEDULED,
                    ]
                );

                $result[] = $session;
            }
        });

        return $result;
    }
}
