<?php

namespace App\Exports;

use App\Models\ReviewPlan;
use App\Models\QuranVerse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Str;

class ReviewPlanExport implements FromCollection, WithHeadings
{
    protected int $studentId;

    public function __construct(int $studentId)
    {
        $this->studentId = $studentId;
    }

    public function collection(): Collection
    {
        $plans = ReviewPlan::where('student_id', $this->studentId)
            ->orderBy('day_number')
            ->get(['day_number', 'from_verse_id', 'to_verse_id', 'type']);

        $verseIds = $plans->pluck('from_verse_id')
            ->merge($plans->pluck('to_verse_id'))
            ->unique();

        $verses = QuranVerse::with('surah')
            ->whereIn('id', $verseIds)
            ->get()
            ->keyBy('id');

        // تجهيز البيانات المفصلة
        return $plans->map(function ($plan) use ($verses) {
            $from = $verses[$plan->from_verse_id] ?? null;
            $to = $verses[$plan->to_verse_id] ?? null;

            return [
                'اليوم' => $plan->day_number,
                'من (السورة)' => $from?->surah->name ?? 'غير معروف',
                'من (رقم الآية)' => $from?->ayah ?? '-',
                'من (نص الآية)' => $from ? Str::limit($from->text, 40) : '-',
                'إلى (السورة)' => $to?->surah->name ?? 'غير معروف',
                'إلى (رقم الآية)' => $to?->ayah ?? '-',
                'إلى (نص الآية)' => $to ? Str::limit($to->text, 40) : '-',
                'الدرس' => $plan->type === 'new' ? 'جديد' : 'مراجعة',

            ];
        });
    }

    public function headings(): array
    {
        return [
            'اليوم',
            'من (السورة)',
            'من (رقم الآية)',
            'من (نص الآية)',
            'إلى (السورة)',
            'إلى (رقم الآية)',
            'إلى (نص الآية)',
            'الدرس',

        ];
    }
}
