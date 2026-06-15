<?php

namespace App\Services;

use App\Models\RecitationHistory;
use App\Models\RecitationSession;
use Carbon\Carbon;

class SurahRecitationSummaryService
{
    /**
     * ملخّص على مستوى السورة لتلاوة الإمام: يُجمّع جدولة الجلسات والقراءات
     * المكتملة (الأرشيف) بـ GROUP BY surah بدل استدعاء حالة الآية لكل آية.
     *
     * تُعتبر السورة ضمن المقطع إذا تحقّق start_surah <= surah <= end_surah.
     *
     * @return array<int, array<string, mixed>> السور التي لها أي سجل، مرتّبة تصاعديًا.
     */
    public function getSummary(int $userId, ?string $prayer = null): array
    {
        $scheduledDates = $this->collectScheduledDates($userId, $prayer);
        $recitedDates = $this->collectRecitedDates($userId, $prayer);

        $surahs = array_unique(array_merge(
            array_keys($scheduledDates),
            array_keys($recitedDates)
        ));
        sort($surahs, SORT_NUMERIC);

        $today = now()->startOfDay();
        $data = [];

        foreach ($surahs as $surah) {
            $scheduled = $scheduledDates[$surah] ?? [];
            $recited = $recitedDates[$surah] ?? [];

            $lastRecitedAt = $this->maxDate($recited);
            $lastScheduledDate = $this->maxDate($scheduled);

            $data[] = [
                'surah' => $surah,
                'last_recited_at' => $lastRecitedAt,
                'days_since_last_recitation' => $lastRecitedAt === null
                    ? null
                    : (int) Carbon::parse($lastRecitedAt)->diffInDays($today),
                'last_scheduled_date' => $lastScheduledDate,
                'times_recited' => count($recited),
                'times_scheduled' => count($scheduled),
            ];
        }

        return $data;
    }

    /**
     * @return array<int, array<string, true>> surah => مجموعة أيام الجدولة المميّزة.
     */
    private function collectScheduledDates(int $userId, ?string $prayer): array
    {
        $rows = RecitationSession::query()
            ->where('recitation_sessions.user_id', $userId)
            ->when($prayer !== null, fn ($q) => $q->where('recitation_sessions.prayer_name', $prayer))
            ->join('recitation_segments', 'recitation_segments.session_id', '=', 'recitation_sessions.id')
            ->get([
                'recitation_sessions.date as date',
                'recitation_segments.start_surah as start_surah',
                'recitation_segments.end_surah as end_surah',
            ]);

        return $this->groupDatesBySurah($rows);
    }

    /**
     * @return array<int, array<string, true>> surah => مجموعة أيام القراءة المميّزة.
     */
    private function collectRecitedDates(int $userId, ?string $prayer): array
    {
        $rows = RecitationHistory::query()
            ->where('user_id', $userId)
            ->when($prayer !== null, fn ($q) => $q->where('prayer_name', $prayer))
            ->get(['date', 'start_surah', 'end_surah']);

        return $this->groupDatesBySurah($rows);
    }

    /**
     * يفكّ نطاق السور لكل سجل (start_surah..end_surah) ويجمع التواريخ المميّزة لكل سورة.
     *
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $rows
     * @return array<int, array<string, true>>
     */
    private function groupDatesBySurah($rows): array
    {
        $bySurah = [];

        foreach ($rows as $row) {
            $dateStr = $row->date instanceof Carbon
                ? $row->date->toDateString()
                : (string) $row->date;

            $start = (int) $row->start_surah;
            $end = (int) $row->end_surah;

            for ($surah = $start; $surah <= $end; $surah++) {
                $bySurah[$surah][$dateStr] = true;
            }
        }

        return $bySurah;
    }

    /**
     * @param  array<string, true>  $dates
     */
    private function maxDate(array $dates): ?string
    {
        if ($dates === []) {
            return null;
        }

        return max(array_keys($dates));
    }
}
