<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuranVerse;
use App\Services\ReviewPlanGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ReviewPlan;

class ReviewPlanController extends Controller
{
    public function store(Request $request)
    {
        $student = $request->user()->student;

        $validated = $request->validate([
            'plan' => 'required|array|min:1',
            'plan.*.day_number' => 'required|integer|min:1',
            'plan.*.from_verse_id' => 'required|integer|exists:quran_verses,id',
            'plan.*.to_verse_id' => 'required|integer|exists:quran_verses,id',
            'plan.*.type' => 'nullable|in:review,new',
        ]);

        // استخراج النوع من أول عنصر أو تعيين 'review' كقيمة افتراضية
        $type = $validated['plan'][0]['type'] ?? 'review';

        // 🛠 حذف الخطط السابقة من نفس النوع فقط
        $student->reviewPlans()->where('type', $type)->delete();

        // ✅ إنشاء الخطة الجديدة
        foreach ($validated['plan'] as $day) {
            $student->reviewPlans()->create([
                'day_number' => $day['day_number'],
                'from_verse_id' => $day['from_verse_id'],
                'to_verse_id' => $day['to_verse_id'],
                'type' => $type,
            ]);
        }

        return response()->json(['message' => 'تم حفظ الخطة بنجاح']);
    }


    public function view(Request $request)
    {
        $type = $request->query('type', 'review');

        $plans = $request->user()->student
            ->reviewPlans()
            ->where('type', $type)
            ->orderBy('day_number')
            ->get(['day_number', 'from_verse_id', 'to_verse_id', 'type']);

        $verseIds = $plans->pluck('from_verse_id')
            ->merge($plans->pluck('to_verse_id'))
            ->unique()
            ->values();

        $verses = QuranVerse::with('surah')
            ->whereIn('id', $verseIds)
            ->get()
            ->keyBy('id');

        $formatted = $plans->map(function ($plan) use ($verses) {
            $from = $verses[$plan->from_verse_id] ?? null;
            $to = $verses[$plan->to_verse_id] ?? null;

            return [
                'day_number' => $plan->day_number,
                'type' => $plan->type,
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
            'days' => $formatted,
        ]);
    }
    // حذف جدول المراجعة أو الحفظ
    public function destroy(Request $request)
    {
        $user = Auth::user();
        $student = $user->student;

        $type = $request->query('type');
        if (!in_array($type, ['review', 'new'])) {
            return response()->json(['message' => 'نوع الجدول غير صالح.'], 422);
        }

        $plans = ReviewPlan::where('student_id', $student->id)
            ->where('type', $type)
            ->get();

        if ($plans->isEmpty()) {
            return response()->json(['message' => 'لا يوجد جدول للحذف.'], 404);
        }

        foreach ($plans as $plan) {
            $plan->delete();
        }

        return response()->json(['message' => 'تم حذف الجدول بنجاح.']);
    }

}
