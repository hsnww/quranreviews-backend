<?php

namespace App\Services;

use App\Models\RecitationHistory;
use App\Models\RecitationSession;
use Carbon\Carbon;

class AyahRecitationStatusService
{
    /**
     * @return array<string, mixed>
     */
    public function getStatus(int $userId, int $surah, int $ayah): array
    {
        $scheduledQuery = RecitationSession::query()
            ->where('user_id', $userId)
            ->whereHas('segments', function ($q) use ($surah, $ayah) {
                $q->where(function ($rangeQ) use ($surah, $ayah) {
                    $rangeQ->where('start_surah', $surah)
                        ->where('end_surah', $surah)
                        ->where('start_ayah', '<=', $ayah)
                        ->where('end_ayah', '>=', $ayah);
                })->orWhere(function ($rangeQ) use ($surah, $ayah) {
                    $rangeQ->where('start_surah', $surah)
                        ->where('end_surah', $surah + 1)
                        ->where('start_ayah', '<=', $ayah);
                })->orWhere(function ($rangeQ) use ($surah, $ayah) {
                    $rangeQ->where('start_surah', $surah - 1)
                        ->where('end_surah', $surah)
                        ->where('end_ayah', '>=', $ayah);
                });
            });

        $scheduledSessions = (clone $scheduledQuery)
            ->orderBy('date')
            ->orderBy('id')
            ->get(['date', 'prayer_name']);

        $today = now()->toDateString();
        $scheduledSessionsList = [];
        $futureScheduled = false;
        $nextScheduledDate = null;

        foreach ($scheduledSessions as $session) {
            $dateStr = $session->date instanceof Carbon
                ? $session->date->toDateString()
                : (string) $session->date;

            $scheduledSessionsList[] = [
                'date' => $dateStr,
                'prayer_name' => $session->prayer_name,
            ];

            if ($dateStr > $today) {
                $futureScheduled = true;
                if ($nextScheduledDate === null || $dateStr < $nextScheduledDate) {
                    $nextScheduledDate = $dateStr;
                }
            }
        }

        $timesScheduled = count($scheduledSessionsList);

        $lastHistory = RecitationHistory::query()
            ->where('user_id', $userId)
            ->where(function ($q) use ($surah, $ayah) {
                $q->where(function ($rangeQ) use ($surah, $ayah) {
                    $rangeQ->where('start_surah', $surah)
                        ->where('end_surah', $surah)
                        ->where('start_ayah', '<=', $ayah)
                        ->where('end_ayah', '>=', $ayah);
                })->orWhere(function ($rangeQ) use ($surah, $ayah) {
                    $rangeQ->where('start_surah', $surah)
                        ->where('end_surah', $surah + 1)
                        ->where('start_ayah', '<=', $ayah);
                })->orWhere(function ($rangeQ) use ($surah, $ayah) {
                    $rangeQ->where('start_surah', $surah - 1)
                        ->where('end_surah', $surah)
                        ->where('end_ayah', '>=', $ayah);
                });
            })
            ->latest('date')
            ->latest('id')
            ->first();

        $daysSince = null;
        if ($lastHistory !== null) {
            $daysSince = Carbon::parse($lastHistory->date)->diffInDays(now()->startOfDay());
        }

        $timesRecited = RecitationHistory::query()
            ->where('user_id', $userId)
            ->where(function ($q) use ($surah, $ayah) {
                $q->where(function ($rangeQ) use ($surah, $ayah) {
                    $rangeQ->where('start_surah', $surah)
                        ->where('end_surah', $surah)
                        ->where('start_ayah', '<=', $ayah)
                        ->where('end_ayah', '>=', $ayah);
                })->orWhere(function ($rangeQ) use ($surah, $ayah) {
                    $rangeQ->where('start_surah', $surah)
                        ->where('end_surah', $surah + 1)
                        ->where('start_ayah', '<=', $ayah);
                })->orWhere(function ($rangeQ) use ($surah, $ayah) {
                    $rangeQ->where('start_surah', $surah - 1)
                        ->where('end_surah', $surah)
                        ->where('end_ayah', '>=', $ayah);
                });
            })
            ->count();

        return [
            'future_scheduled' => $futureScheduled,
            'next_scheduled_date' => $nextScheduledDate,
            'scheduled_sessions' => $scheduledSessionsList,
            'last_recited_at' => $lastHistory?->date?->toDateString(),
            'last_recited_prayer' => $lastHistory?->prayer_name,
            'days_since_last_recitation' => $daysSince,
            'times_scheduled' => $timesScheduled,
            'times_recited' => $timesRecited,
        ];
    }
}
