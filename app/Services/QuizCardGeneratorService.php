<?php

namespace App\Services;

use App\Models\QuranVerse;
use App\Models\Student;
use App\Models\StudentMemorization;
use Illuminate\Support\Collection;

/**
 * Memorization quiz windows: fixed {@see $versesPerCard}, optional per-juz coverage.
 *
 * Memorization source matches {@see StudentMemorization} inclusive verse-id ranges
 * ∩ {@see QuranVerse::$jozo} ∈ selected juz ids.
 *
 * @phpstan-type Window array{unique_key:string, sora_number:int, verse_ids:list<int>, jozo:int, from_verse_id:int, to_verse_id:int}
 */
final class QuizCardGeneratorService
{
    /**
     * @param  array<int>  $juzIds  1–30 (`quran_verses.jozo`).
     * @return array{cards: list<Window>, warnings: list<string>, failure_message: ?string}
     */
    public function generate(
        Student $student,
        array $juzIds,
        int $requestedCardCount,
        int $versesPerCard,
        bool $ensureJuzCoverage,
    ): array {
        $juzIds = array_values(array_unique(array_map('intval', $juzIds)));
        $warnings = [];

        $memorizedIds = $this->memorizedVerseIds($student);
        if ($memorizedIds->isEmpty()) {
            return [
                'cards' => [],
                'warnings' => [],
                'failure_message' => 'لا يوجد محفوظ مسجل لهذا الطالب.',
            ];
        }

        $candidates = QuranVerse::query()
            ->whereIn('id', $memorizedIds)
            ->whereIn('jozo', $juzIds)
            ->orderBy('id')
            ->get(['id', 'sora', 'ayah', 'jozo']);

        if ($candidates->isEmpty()) {
            return [
                'cards' => [],
                'warnings' => [],
                'failure_message' => 'لا توجد آيات محفوظة ضمن الأجزاء المختارة.',
            ];
        }

        $fullPool = $this->collectWindowsOfLength($candidates, $versesPerCard);
        if ($fullPool === []) {
            return [
                'cards' => [],
                'warnings' => [],
                'failure_message' => sprintf(
                    'لا يمكن تشكيل بطاقة بطول %d آيات متتابعة ضمن المحفوظ والأجزاء المختارة.',
                    $versesPerCard
                ),
            ];
        }

        $usedUniqueKeys = [];
        $cards = [];

        if ($ensureJuzCoverage) {
            foreach ($juzIds as $juz) {
                $subset = $candidates->filter(fn (QuranVerse $v) => (int) $v->jozo === $juz)->values();
                $pool = $this->collectWindowsOfLength($subset, $versesPerCard);
                $choice = $this->pickRandomUniqueWindowWithRetry($pool, $usedUniqueKeys, 30);
                if ($choice === null) {
                    return [
                        'cards' => [],
                        'warnings' => [],
                        'failure_message' => sprintf(
                            'تعذّر تغطية الجزء %d: لا توجد نافذة بطول %d آية ضمن المحفوظ في هذا الجزء.',
                            $juz,
                            $versesPerCard
                        ),
                    ];
                }
                $cards[] = $choice;
            }
        }

        $maxIterations = max($requestedCardCount * 40, 80);
        $iterations = 0;

        while (count($cards) < $requestedCardCount && $iterations < $maxIterations) {
            $iterations++;
            $choice = $this->pickRandomUniqueWindowWithRetry($fullPool, $usedUniqueKeys, 30);
            if ($choice === null) {
                break;
            }
            $cards[] = $choice;
        }

        if (count($cards) < $requestedCardCount) {
            return [
                'cards' => [],
                'warnings' => [],
                'failure_message' => sprintf(
                    'تعذّر توليد %d بطاقة متميزة بطول %d آية لكل بطاقة؛ تأكد من المحفوظ أو خفّض عدد البطاقات.',
                    $requestedCardCount,
                    $versesPerCard
                ),
            ];
        }

        return [
            'cards' => $cards,
            'warnings' => $warnings,
            'failure_message' => null,
        ];
    }

    /**
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
     * @return list<Window>
     */
    private function collectWindowsOfLength(Collection $candidates, int $n): array
    {
        if ($n < 1) {
            return [];
        }

        $windows = [];

        foreach ($candidates->groupBy('sora') as $soraNumber => $verses) {
            $sorted = $verses->sortBy('ayah')->values();
            $count = $sorted->count();

            for ($i = 0; $i <= $count - $n; $i++) {
                $window = $sorted->slice($i, $n)->values();
                if (!$this->isConsecutiveAyahs($window)) {
                    continue;
                }

                $verseIds = $window->pluck('id')->map(fn ($id) => (int) $id)->all();
                $first = $window->first();
                $fromVerseId = $verseIds[0];
                $toVerseId = $verseIds[count($verseIds) - 1];
                $windows[] = [
                    'unique_key' => sprintf('%d-%d-%d', (int) $soraNumber, $fromVerseId, $toVerseId),
                    'sora_number' => (int) $soraNumber,
                    'verse_ids' => $verseIds,
                    'jozo' => (int) $first->jozo,
                    'from_verse_id' => $fromVerseId,
                    'to_verse_id' => $toVerseId,
                ];
            }
        }

        shuffle($windows);

        return $windows;
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
     * @param  list<Window>  $pool
     * @param  array<string, bool>  $usedUniqueKeys
     */
    private function pickRandomUniqueWindowWithRetry(array $pool, array &$usedUniqueKeys, int $maxAttempts): ?array
    {
        if ($pool === []) {
            return null;
        }

        $attempt = 0;
        while ($attempt < $maxAttempts) {
            $attempt++;
            $choice = $pool[array_rand($pool)];
            $key = (string) $choice['unique_key'];
            if (!isset($usedUniqueKeys[$key])) {
                $usedUniqueKeys[$key] = true;
                return $choice;
            }
        }

        // Final deterministic fallback when random retries hit duplicates.
        foreach ($pool as $choice) {
            $key = (string) $choice['unique_key'];
            if (!isset($usedUniqueKeys[$key])) {
                $usedUniqueKeys[$key] = true;
                return $choice;
            }
        }

        return null;
    }
}
