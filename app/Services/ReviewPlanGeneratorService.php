<?php

namespace App\Services;

use App\Models\ReviewPlan;
use App\Models\QuranVerse;
use App\Models\Student;
use App\Models\StudentMemorization;

class ReviewPlanGeneratorService
{

    public function generateFromSelections(int $studentId, array $memorizationIds): void
    {
        $verses = collect();

        foreach ($memorizationIds as $id) {
            $memorization = StudentMemorization::findOrFail($id);
            $selection = QuranVerse::whereBetween('id', [$memorization->from_verse_id, $memorization->to_verse_id])->get();
            $verses = $verses->concat($selection);
        }

        $student = Student::findOrFail($studentId);

        $reviewDays = $student->preferred_review_days ?? 7;
        $chunks = $verses->chunk(ceil($verses->count() / $reviewDays));

        ReviewPlan::where('student_id', $studentId)->delete(); // حذف الجدول السابق إن وجد

        foreach ($chunks as $dayNumber => $chunk) {
            $from = $chunk->first();
            $to = $chunk->last();

            ReviewPlan::create([
                'student_id' => $studentId,
                'day_number' => $dayNumber + 1,
                'from_verse_id' => $from->id,
                'to_verse_id' => $to->id,
                'type' => 'review',
            ]);
        }
    }

    public function generateFromSelection(int $studentId, array $selections): void
    {
        foreach ($selections as $dayNumber => $range) {
            ReviewPlan::create([
                'student_id' => $studentId,
                'day_number' => $dayNumber + 1,
                'from_verse_id' => $range['from_verse_id'],
                'to_verse_id' => $range['to_verse_id'],
                'type' => 'review',
            ]);
        }
    }

}
