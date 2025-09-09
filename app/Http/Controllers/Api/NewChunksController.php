<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentMemorization;
use App\Models\QuranVerse;
use App\Models\Surah; // تأكد من إضافة الـ import الخاص بالسورة

class NewChunksController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'الطالب غير موجود'], 404);
        }

        // جلب الآيات المحفوظة
        $memorizedVerseIds = StudentMemorization::where('student_id', $student->id)
            ->select('from_verse_id', 'to_verse_id')
            ->get()
            ->flatMap(fn($mem) => range($mem->from_verse_id, $mem->to_verse_id))
            ->unique()
            ->toArray();

        // الآيات غير المحفوظة
        $unmemorized = QuranVerse::whereNotIn('id', $memorizedVerseIds)->get();

        // تحديد السور غير المحفوظة بالكامل
        $unmemorizedBySurah = $unmemorized->groupBy('sora')->map(function ($verses, $soraNumber) {
            return [
                'sora_number' => $soraNumber,
                'sora_name' => Surah::find($soraNumber)?->name ?? 'غير معروف',
                'verses_count' => $verses->count(),
                'start_page' => $verses->min('page'),
                'end_page' => $verses->max('page'),
            ];
        })->values();

        return response()->json($unmemorizedBySurah);
    }

    protected function splitByFraction($verses, $parts)
    {
        return $verses
            ->groupBy('hizb')
            ->flatMap(function ($group) use ($parts) {
                $chunks = $group->chunk(ceil($group->count() / $parts));
                return $chunks->mapWithKeys(function ($chunk, $index) use ($group) {
                    $key = $group->first()->hizb . '-' . ($index + 1);
                    return [$key => $chunk];
                });
            });
    }

    public function showSurah(Request $request, $soraNumber)
    {
        $user = $request->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'الطالب غير موجود'], 404);
        }

        // الآيات المحفوظة
        $memorizedVerseIds = StudentMemorization::where('student_id', $student->id)
            ->select('from_verse_id', 'to_verse_id')
            ->get()
            ->flatMap(fn($mem) => range($mem->from_verse_id, $mem->to_verse_id))
            ->unique()
            ->toArray();

        $verses = QuranVerse::where('sora', $soraNumber)
            ->whereNotIn('id', $memorizedVerseIds)
            ->orderBy('ayah')
            ->get();

        $groupedByPage = $verses->groupBy('page')->map(function ($group) {
            return [
                'from_verse_id' => $group->first()->id,
                'to_verse_id' => $group->last()->id,
                'page' => $group->first()->page,
                'from_ayah' => $group->first()->ayah,
                'to_ayah' => $group->last()->ayah,
                'from_text' => $group->first()->text,
                'to_text' => $group->last()->text,
            ];
        })->values();

        return response()->json($groupedByPage);
    }
    public function listUnmemorizedSurahs(Request $request)
    {
        $user = $request->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'الطالب غير موجود'], 404);
        }

        $memorizedVerseIds = StudentMemorization::where('student_id', $student->id)
            ->select('from_verse_id', 'to_verse_id')
            ->get()
            ->flatMap(fn($mem) => range($mem->from_verse_id, $mem->to_verse_id))
            ->unique()
            ->toArray();

        $unmemorizedVerses = QuranVerse::whereNotIn('id', $memorizedVerseIds)->get();

        $unmemorizedBySurah = $unmemorizedVerses
            ->groupBy('sora')
            ->map(function ($verses, $soraNumber) {
                return [
                    'sora_number' => (int) $soraNumber,
                    'sora_name' => Surah::find($soraNumber)?->name ?? 'غير معروف',
                    'verses_count' => $verses->count(),
                    'start_page' => $verses->min('page'),
                    'end_page' => $verses->max('page'),
                ];
            })
            ->values();

        return response()->json($unmemorizedBySurah);
    }
    public function getSurahPages(Request $request, $soraNumber)
    {
        $user = $request->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'الطالب غير موجود'], 404);
        }

        $memorizedVerseIds = StudentMemorization::where('student_id', $student->id)
            ->select('from_verse_id', 'to_verse_id')
            ->get()
            ->flatMap(fn($mem) => range($mem->from_verse_id, $mem->to_verse_id))
            ->unique()
            ->toArray();

        $verses = QuranVerse::where('sora', $soraNumber)
            ->whereNotIn('id', $memorizedVerseIds)
            ->orderBy('ayah')
            ->get();

        if ($verses->isEmpty()) {
            return response()->json([]);
        }

        $groupedByPage = $verses->groupBy('page')->map(function ($group) {
            $first = $group->first();
            return [
                'page' => $first->page,
                'from_verse_id' => $first->id,
                'to_verse_id' => $group->last()->id,
                'from_ayah' => $first->ayah,
                'to_ayah' => $group->last()->ayah,
                'from_text' => $first->text,
                'to_text' => $group->last()->text,
                'jozo' => $first->jozo,
                'hizb' => $first->hizb,
                'qrtr' => $first->qrtr,
            ];
        })->values();

        return response()->json($groupedByPage);
    }

}
