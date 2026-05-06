<?php

namespace App\Services;

use App\Models\QuranVerse;
use App\Models\Student;
use App\Models\StudentMemorization;
use Illuminate\Support\Collection;

/**
 * Builds memorization-quiz card windows for a student session.
 *
 * Memorization source (matches existing app behaviour):
 * - Student rows in {@see StudentMemorization} define inclusive verse-id ranges
 *   (`from_verse_id` … `to_verse_id`).
 * - A verse is eligible only if its `id` falls inside at least one such range
 *   (same pattern as `reviewChunks` / `getMemorizationChunks`).
 * - Quiz candidates are those eligible verses further filtered by `jozo` ∈ selected juz ids
 *   (intersection with session parts).
 *
 * Card windows:
 * - Group candidates by surah (`sora`).
 * - Within each surah, only sequences of consecutive `ayah` numbers are allowed (no cross-surah).
 * - Window length N is chosen per card from {4, 5, 6}.
 * - Duplicate identical verse-id sequences in one session are avoided when possible.
 */
final class QuizCardGeneratorService
{
    private const MIN_WINDOW = 4;

    private const MAX_WINDOW = 6;

    /**
     * @param  array<int>  $juzIds  Values 1–30 (`quran_verses.jozo`).
     * @return array{cards: list<array{sora_number:int, verse_ids:list<int>}>, actual_count: int, warnings: list<string>}
     */
    public function generate(Student $student, array $juzIds, int $requestedCardCount): array
    {
        $juzIds = array_values(array_unique(array_map('intval', $juzIds)));

        $memorizedIds = $this->memorizedVerseIds($student);
        if ($memorizedIds->isEmpty()) {
            return ['cards' => [], 'actual_count' => 0, 'warnings' => ['لا يوجد محفوظ مسجل لهذا الطالب.']];
        }

        $candidates = QuranVerse::query()
            ->whereIn('id', $memorizedIds)
            ->whereIn('jozo', $juzIds)
            ->orderBy('id')
            ->get(['id', 'sora', 'ayah', 'text']);

        if ($candidates->isEmpty()) {
            return ['cards' => [], 'actual_count' => 0, 'warnings' => ['لا توجد آيات محفوظة ضمن الأجزاء المختارة.']];
        }

        $windowsByLength = $this->collectWindowsByLength($candidates);

        $usedFingerprints = [];
        $cards = [];
        $warnings = [];

        $maxIterations = max($requestedCardCount * 25, 50);
        $iterations = 0;

        while (count($cards) < $requestedCardCount && $iterations < $maxIterations) {
            $iterations++;

            $order = [4, 5, 6];
            shuffle($order);

            $choice = null;
            foreach ($order as $len) {
                $choice = $this->pickRandomUnusedWindow($windowsByLength[$len] ?? [], $usedFingerprints);
                if ($choice !== null) {
                    break;
                }
            }

            if ($choice === null) {
                break;
            }

            $fp = $choice['fingerprint'];
            $usedFingerprints[$fp] = true;
            $cards[] = [
                'sora_number' => $choice['sora_number'],
                'verse_ids' => $choice['verse_ids'],
            ];
        }

        $actual = count($cards);
        if ($actual < $requestedCardCount && $actual > 0) {
            $warnings[] = sprintf(
                'تعذّر توليد العدد المطلوب بالكامل؛ تم إنشاء %d بطاقة بدلاً من %d.',
                $actual,
                $requestedCardCount
            );
        }

        return [
            'cards' => $cards,
            'actual_count' => $actual,
            'warnings' => $warnings,
        ];
    }

    /**
     * Verse ids considered memorized for the student (union of inclusive ranges).
     *
     * @return Collection<int, int>
     */
    public function memorizedVerseIds(Student $student): Collection
    {
        $ids = collect();

        $ranges = StudentMemorization::query()
            ->where('student_id', $student->id)
            ->get(['from_verse_id', 'to_verse_id']);

        foreach ($ranges as $part) {
            $from = min($part->from_verse_id, $part->to_verse_id);
            $to = max($part->from_verse_id, $part->to_verse_id);
            $ids = $ids->merge(range($from, $to));
        }

        return $ids->unique()->values();
    }

    /**
     * For each juz in $juzIds, true when at least one memorized verse has that `jozo`.
     *
     * @param  array<int>  $juzIds
     */
    public function juzHasMemorizedAyah(Student $student, int $juz): bool
    {
        $memIds = $this->memorizedVerseIds($student);
        if ($memIds->isEmpty()) {
            return false;
        }

        return QuranVerse::query()
            ->whereIn('id', $memIds)
            ->where('jozo', $juz)
            ->exists();
    }

    /**
     * @return array<int, list<array{fingerprint:string, sora_number:int, verse_ids:list<int>}>>
     */
    private function collectWindowsByLength(Collection $candidates): array
    {
        $byLength = [4 => [], 5 => [], 6 => []];

        foreach ($candidates->groupBy('sora') as $soraNumber => $verses) {
            /** @var Collection<int, QuranVerse> $sorted */
            $sorted = $verses->sortBy('ayah')->values();
            $count = $sorted->count();

            for ($n = self::MIN_WINDOW; $n <= self::MAX_WINDOW; $n++) {
                for ($i = 0; $i <= $count - $n; $i++) {
                    $window = $sorted->slice($i, $n)->values();
                    if (!$this->isConsecutiveAyahs($window)) {
                        continue;
                    }
                    $verseIds = $window->pluck('id')->map(fn ($id) => (int) $id)->all();
                    $fp = implode(',', $verseIds);
                    $byLength[$n][] = [
                        'fingerprint' => $fp,
                        'sora_number' => (int) $soraNumber,
                        'verse_ids' => $verseIds,
                    ];
                }
            }
        }

        foreach ($byLength as $n => $list) {
            shuffle($byLength[$n]);
        }

        return $byLength;
    }

    /**
     * @param  Collection<int, QuranVerse>  $window
     */
    private function isConsecutiveAyahs(Collection $window): bool
    {
        $prev = null;
        foreach ($window as $v) {
            if ($prev !== null && (int) $v->ayah !== $prev + 1) {
                return false;
            }
            $prev = (int) $v->ayah;
        }

        return true;
    }

    /**
     * @param  list<array{fingerprint:string, sora_number:int, verse_ids:list<int>}>  $pool
     * @param  array<string, bool>  $usedFingerprints
     * @return array{fingerprint:string, sora_number:int, verse_ids:list<int>}|null
     */
    private function pickRandomUnusedWindow(array $pool, array &$usedFingerprints): ?array
    {
        $available = array_values(array_filter($pool, fn ($w) => empty($usedFingerprints[$w['fingerprint']])));
        if ($available === []) {
            return null;
        }

        return $available[array_rand($available)];
    }
}
