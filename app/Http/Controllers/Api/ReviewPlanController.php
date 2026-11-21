<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuranVerse;
use App\Models\Student;
use App\Models\StudyPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewPlanController extends Controller
{
    /** قائمة خطط الطالب (الأحدث أولًا)، ويمكن تصفيتها بالنوع review | new */
    public function index(Request $request)
    {
        $type = $request->query('type');

        $plans = $request->user()->student
            ->studyPlans()
            ->when(in_array($type, ['review', 'new'], true), fn ($q) => $q->where('type', $type))
            ->latest('id')
            ->get()
            ->map(fn (StudyPlan $plan) => $plan->toSummary());

        return response()->json(['plans' => $plans]);
    }

    /** إنشاء خطة جديدة دون المساس بالخطط السابقة */
    public function store(Request $request)
    {
        $student = $request->user()->student;

        $validated = $request->validate([
            'title' => 'nullable|string|max:100',
            // اليوم الأول في الخطة (الأيام نفسها تُرسل بتواريخها محسوبة منه)
            'start_date' => 'nullable|date_format:Y-m-d',
            'plan' => 'required|array|min:1',
            'plan.*.day_number' => 'required|integer|min:1',
            'plan.*.from_verse_id' => 'required|integer|exists:quran_verses,id',
            'plan.*.to_verse_id' => 'required|integer|exists:quran_verses,id',
            'plan.*.type' => 'nullable|in:review,new',
            // صفوف «مراجعة السابق» المضمّنة في جدول الحفظ الجديد
            'plan.*.is_revision' => 'nullable|boolean',
            // تاريخ اليوم في التقويم، ومراجعة الجمعة/السبت لدروس الأسبوع
            'plan.*.date' => 'nullable|date_format:Y-m-d',
            'plan.*.is_weekly_review' => 'nullable|boolean',
        ]);

        // استخراج النوع من أول عنصر أو تعيين 'review' كقيمة افتراضية
        $type = $validated['plan'][0]['type'] ?? 'review';

        $startDate = $validated['start_date']
            ?? collect($validated['plan'])->pluck('date')->filter()->min();

        $plan = DB::transaction(function () use ($student, $validated, $type, $startDate) {
            $plan = $student->studyPlans()->create([
                'type' => $type,
                'title' => filled($validated['title'] ?? null) ? trim($validated['title']) : null,
                'start_date' => $startDate,
            ]);

            foreach ($validated['plan'] as $day) {
                $plan->days()->create([
                    'student_id' => $student->id,
                    'day_number' => $day['day_number'],
                    'from_verse_id' => $day['from_verse_id'],
                    'to_verse_id' => $day['to_verse_id'],
                    'type' => $type,
                    'is_revision' => $type === 'new' && !empty($day['is_revision']),
                    'date' => $day['date'] ?? null,
                    'is_weekly_review' => !empty($day['is_weekly_review']),
                ]);
            }

            return $plan;
        });

        return response()->json([
            'message' => 'تم حفظ الخطة بنجاح',
            'plan' => $plan->toSummary(),
        ], 201);
    }

    /**
     * أيام خطة معيّنة (plan_id)، وإن لم تُحدَّد فأحدث خطة من النوع المطلوب.
     */
    public function view(Request $request)
    {
        $student = $request->user()->student;
        $type = $request->query('type', 'review');

        $plan = self::resolvePlan($student, $request->query('plan_id'), $type);
        if ($plan === false) {
            return response()->json(['message' => 'الخطة غير موجودة.'], 404);
        }
        if (!$plan) {
            return response()->json(['plan' => null, 'days' => []]);
        }

        $days = $plan->days()
            ->orderBy('day_number')
            ->orderBy('id')
            ->get(['day_number', 'date', 'from_verse_id', 'to_verse_id', 'type', 'is_revision', 'is_weekly_review']);

        $verseIds = $days->pluck('from_verse_id')
            ->merge($days->pluck('to_verse_id'))
            ->unique()
            ->values();

        $verses = QuranVerse::with('surah')
            ->whereIn('id', $verseIds)
            ->get()
            ->keyBy('id');

        $formatted = $days->map(function ($day) use ($verses) {
            $from = $verses[$day->from_verse_id] ?? null;
            $to = $verses[$day->to_verse_id] ?? null;

            return [
                'day_number' => $day->day_number,
                'type' => $day->type,
                'is_revision' => (bool) $day->is_revision,
                'is_weekly_review' => (bool) $day->is_weekly_review,
                'date' => $day->date?->format('Y-m-d'),
                'from' => $from ? [
                    'sora' => $from->surah->name ?? 'غير معروف',
                    'ayah_number' => $from->ayah,
                    'text' => $from->text,
                ] : null,
                'to' => $to ? [
                    'sora' => $to->surah->name ?? 'غير معروف',
                    'ayah_number' => $to->ayah,
                    'text' => $to->text,
                ] : null,
            ];
        });

        return response()->json([
            'plan' => $plan->toSummary(),
            'days' => $formatted,
        ]);
    }

    /** تعديل اسم الخطة */
    public function update(Request $request, StudyPlan $plan)
    {
        if ($plan->student_id !== $request->user()->student?->id) {
            return response()->json(['message' => 'الخطة غير موجودة.'], 404);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:100',
        ]);

        $plan->update([
            'title' => filled($validated['title'] ?? null) ? trim($validated['title']) : null,
        ]);

        return response()->json(['plan' => $plan->toSummary()]);
    }

    /** حذف خطة واحدة: DELETE /review-plans/{plan} */
    public function destroyPlan(Request $request, StudyPlan $plan)
    {
        if ($plan->student_id !== $request->user()->student?->id) {
            return response()->json(['message' => 'الخطة غير موجودة.'], 404);
        }
        $plan->delete(); // الأيام تُحذف معها (cascade)
        return response()->json(['message' => 'تم حذف الخطة بنجاح.']);
    }

    /**
     * حذف خطة بمعرّفها (plan_id)، أو كل خطط النوع (type) للتوافق مع الواجهات القديمة.
     */
    public function destroy(Request $request)
    {
        $student = $request->user()->student;
        $planId = $request->query('plan_id');

        if ($planId) {
            $target = $student->studyPlans()->find($planId);
            if (!$target) {
                return response()->json(['message' => 'الخطة غير موجودة.'], 404);
            }
            $target->delete(); // الأيام تُحذف معها (cascade)
            return response()->json(['message' => 'تم حذف الخطة بنجاح.']);
        }

        $type = $request->query('type');
        if (!in_array($type, ['review', 'new'], true)) {
            return response()->json(['message' => 'نوع الجدول غير صالح.'], 422);
        }

        $plans = $student->studyPlans()->where('type', $type)->get();
        $legacyRows = $student->reviewPlans()->where('type', $type)->whereNull('study_plan_id');

        if ($plans->isEmpty() && !$legacyRows->exists()) {
            return response()->json(['message' => 'لا يوجد جدول للحذف.'], 404);
        }

        $plans->each->delete();
        $legacyRows->delete();

        return response()->json(['message' => 'تم حذف الجدول بنجاح.']);
    }

    /**
     * الخطة المطلوبة: بمعرّفها إن أُرسل (false إن لم تكن للطالب)، وإلا أحدث خطة من النوع (أو null).
     */
    public static function resolvePlan(Student $student, mixed $planId, ?string $type): StudyPlan|false|null
    {
        if (filled($planId)) {
            return $student->studyPlans()->find($planId) ?? false;
        }

        return $student->studyPlans()
            ->when(in_array($type, ['review', 'new'], true), fn ($q) => $q->where('type', $type))
            ->latest('id')
            ->first();
    }
}
