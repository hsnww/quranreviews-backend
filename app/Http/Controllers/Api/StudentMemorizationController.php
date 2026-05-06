<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuranVerse;
use App\Models\StudentMemorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Surah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StudentMemorizationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $memorized = StudentMemorization::with(['fromVerse.surah', 'toVerse.surah'])
            ->where('student_id', $user->student->id)
            ->get();

        return response()->json(['data' => $memorized]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_verse_id' => 'required|exists:quran_verses,id',
            'to_verse_id' => 'required|exists:quran_verses,id',
            'type' => 'required|in:permanent,temporary,test',
            'note' => 'nullable|string',
        ]);

        $validated['student_id'] = $request->user()->student->id;
        $validated['verified'] = false;

        StudentMemorization::create($validated);

        return response()->json(['message' => 'تمت إضافة المحفوظ بنجاح']);
    }

    public function fetchForReview(Request $request)
    {
        $student = $request->user()->student;

        $memorization = $student->memorizedParts()  // نفترض أن العلاقة اسمها memorizedParts
        ->with(['fromVerse.surah', 'toVerse.surah'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'from_verse_id' => $item->from_verse_id,
                    'to_verse_id' => $item->to_verse_id,
                    'from_verse' => [
                        'text' => $item->fromVerse->text,
                        'sora' => $item->fromVerse->sora,
                        'ayah' => $item->fromVerse->ayah,
                    ],
                    'to_verse' => [
                        'text' => $item->toVerse->text,
                        'sora' => $item->toVerse->sora,
                        'ayah' => $item->toVerse->ayah,
                    ],
                    'type' => $item->type,
                    'verified' => $item->verified,
                ];
            });

        return response()->json($memorization);
    }

    public function reviewChunks(Request $request)
    {
        $student = $request->user()->student;
        $group = $request->get('group', 'surah');

        // الآيات التي حفظها الطالب
        $memorizedVerseIds = StudentMemorization::where('student_id', $student->id)
            ->select('from_verse_id', 'to_verse_id')
            ->get()
            ->flatMap(function ($mem) {
                return range($mem->from_verse_id, $mem->to_verse_id);
            })->unique()->toArray();

        $verses = QuranVerse::whereIn('id', $memorizedVerseIds)
            ->with('surah')
            ->orderBy('id')
            ->get();

        if ($verses->isEmpty()) {
            return response()->json([]);
        }

        // اختيار نوع التقسيم
        $chunks = match ($group) {
            'surah' => $verses->groupBy('sora'),
            'jozo' => $verses->groupBy('jozo'),
            'hizb' => $verses->groupBy('hizb'),
            'qrtr' => $verses->groupBy(fn($v) => $v->jozo . '-' . $v->hizb . '-' . $v->qrtr),
            default => $verses->groupBy('sora'),
        };

        $result = [];

        foreach ($chunks as $key => $grouped) {
            $first = $grouped->first();
            $last = $grouped->last();

            $result[] = [
                'from_verse_id' => $first->id,
                'to_verse_id' => $last->id,
                'sora' => $first->surah->name ?? '',
                'sora_number' => $first->sora,
                'ayah' => $first->ayah,
                'text' => $first->text,
                'jozo' => $first->jozo,
                'hizb' => $first->hizb,
                'qrtr' => $first->qrtr,
                'page' => $first->page,
            ];
        }

        return response()->json($result);
    }

    public function memorizedJozosCount(Request $request)
    {
        $student = $request->user()->student;
        $jozoSet = collect();

        foreach ($student->memorizedParts as $part) {
            $fromId = min($part->from_verse_id, $part->to_verse_id);
            $toId = max($part->from_verse_id, $part->to_verse_id);

            $verses = QuranVerse::whereBetween('id', [$fromId, $toId])
                ->select('jozo')
                ->get();

            foreach ($verses as $verse) {
                $jozoSet->push($verse->jozo);
            }
        }

        $uniqueJozos = $jozoSet->unique()->sort()->values();

        return response()->json([
            'count' => $uniqueJozos->count(),
            'jozos' => $uniqueJozos, // لو تحب ترجع قائمة الأجزاء أيضًا
        ]);
    }

    public function getMemorizationChunks(Request $request)
    {
        $student = $request->user()->student;
        $validated = $request->validate([
            'jozo' => 'nullable|integer|between:1,30',
        ]);
        $jozo = $validated['jozo'] ?? null;

        return response()->json(
            Cache::remember(
                $this->memorizationChunksCacheKey($student->id, $jozo),
                now()->addMinutes(3),
                function () use ($student, $jozo) {
                    // جلب مقاطع المحفوظ
                    $memorizedRanges = $student->memorizedParts()
                        ->select('from_verse_id', 'to_verse_id')
                        ->get()
                        ->map(function ($part) {
                            $from = min($part->from_verse_id, $part->to_verse_id);
                            $to = max($part->from_verse_id, $part->to_verse_id);
                            return [$from, $to];
                        });

                    // استعلام واحد فقط لكل الآيات المطلوبة (مع فلترة الجزء إن وُجدت)
                    $versesQuery = QuranVerse::query()
                        ->select('id', 'qrtr', 'jozo', 'hizb', 'sora')
                        ->when($jozo !== null, fn ($q) => $q->where('jozo', $jozo))
                        ->orderBy('jozo')
                        ->orderBy('hizb')
                        ->orderBy('qrtr')
                        ->orderBy('id');

                    $verses = $versesQuery->get();

                    if ($verses->isEmpty()) {
                        return [];
                    }

                    $surahNames = Surah::select('id', 'name')->pluck('name', 'id'); // [78 => "النبأ", ...]

                    $chunks = $verses
                        ->groupBy(fn ($verse) => "{$verse->qrtr}-{$verse->jozo}-{$verse->hizb}-{$verse->sora}")
                        ->map(function ($groupedVerses) use ($memorizedRanges, $surahNames) {
                            $first = $groupedVerses->first();

                            $isMemorized = $groupedVerses->every(function ($verse) use ($memorizedRanges) {
                                foreach ($memorizedRanges as [$from, $to]) {
                                    if ($verse->id >= $from && $verse->id <= $to) {
                                        return true;
                                    }
                                }
                                return false;
                            });

                            return [
                                'qrtr' => $first->qrtr,
                                'jozo' => $first->jozo,
                                'hizb' => $first->hizb,
                                'sora' => $first->sora,
                                'sora_name' => $surahNames[$first->sora] ?? '',
                                'memorized' => $isMemorized,
                            ];
                        })
                        ->values();

                    return $chunks;
                }
            )
        );
    }

    public function updateStudentMemorization(Request $request)
    {
        $student = $request->user()->student;

        $validated = $request->validate([
            'qrtrs' => 'required|array',
            'qrtrs.*' => 'string|regex:/^\\d+-\\d+-\\d+-\\d+$/',
        ]);

        // 🧹 حذف المحفوظات القديمة
        StudentMemorization::where('student_id', $student->id)->delete();

        foreach ($validated['qrtrs'] as $qrtrKey) {
            [$qrtr, $jozo, $hizb, $sora] = explode('-', $qrtrKey);

            // 🕵️‍♂️ استخراج الآيات التي تطابق هذا الربع
            $verses = QuranVerse::where('qrtr', $qrtr)
                ->where('jozo', $jozo)
                ->where('hizb', $hizb)
                ->where('sora', $sora)
                ->orderBy('id')
                ->get();

            if ($verses->isEmpty()) continue;

            StudentMemorization::create([
                'student_id'    => $student->id,
                'from_verse_id' => $verses->first()->id,
                'to_verse_id'   => $verses->last()->id,
                'note'          => null,
                'type'          => 'initial',
                'verified'      => false,
            ]);
        }

        $this->clearMemorizationChunksCache($student->id);

        return response()->json([
            'message' => 'تم حفظ المحفوظات بنجاح.',
        ]);
    }

    private function memorizationChunksCacheKey(int $studentId, ?int $jozo): string
    {
        return sprintf('student_memorization_chunks:%d:%s', $studentId, $jozo ?? 'all');
    }

    private function clearMemorizationChunksCache(int $studentId): void
    {
        Cache::forget($this->memorizationChunksCacheKey($studentId, null));
        for ($jozo = 1; $jozo <= 30; $jozo++) {
            Cache::forget($this->memorizationChunksCacheKey($studentId, $jozo));
        }
    }

    // داخل StudentMemorizationController
    public function listJozos(Request $request)
    {
        $student = $request->user()->student;

        $chunks = $student->memorizedParts;

        $jozos = $chunks->map(function ($part) {
            return QuranVerse::whereBetween('id', [
                min($part->from_verse_id, $part->to_verse_id),
                max($part->from_verse_id, $part->to_verse_id),
            ])->pluck('jozo');
        })->flatten()->unique()->sort()->values();

        return response()->json([
            'jozos' => $jozos
        ]);
    }
    public function getHizbsInJozo(Request $request, int $jozo)
    {
        $hizbs = QuranVerse::where('jozo', $jozo)
            ->select('hizb')
            ->distinct()
            ->orderBy('hizb')
            ->pluck('hizb');

        return response()->json([
            'jozo' => $jozo,
            'hizbs' => $hizbs,
        ]);
    }
    public function getQrtrsInHizb(Request $request, int $hizb)
    {
        $student = $request->user()->student;

        // جلب المقاطع المحفوظة
        $memorizedRanges = $student->memorizedParts->map(function ($part) {
            $from = min($part->from_verse_id, $part->to_verse_id);
            $to = max($part->from_verse_id, $part->to_verse_id);
            return [$from, $to];
        });

        $qrtrs = QuranVerse::where('hizb', $hizb)
            ->select('qrtr', 'jozo', 'hizb', 'sora')
            ->distinct()
            ->orderBy('qrtr')
            ->get();

        $surahNames = Surah::pluck('name', 'id');

        $chunks = $qrtrs->map(function ($chunk) use ($memorizedRanges, $surahNames) {
            $verseIds = QuranVerse::where('qrtr', $chunk->qrtr)->pluck('id');

            $isMemorized = $verseIds->every(function ($verseId) use ($memorizedRanges) {
                foreach ($memorizedRanges as [$from, $to]) {
                    if ($verseId >= $from && $verseId <= $to) {
                        return true;
                    }
                }
                return false;
            });

            return [
                'qrtr' => $chunk->qrtr,
                'jozo' => $chunk->jozo,
                'hizb' => $chunk->hizb,
                'sora' => $chunk->sora,
                'sora_name' => $surahNames[$chunk->sora] ?? '',
                'memorized' => $isMemorized,
            ];
        });

        return response()->json($chunks);
    }


}
