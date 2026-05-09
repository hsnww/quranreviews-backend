<?php

namespace App\Services;

use App\Models\RecitationPlan;
use App\Models\RecitationSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
