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

        $futureScheduled = (clone $scheduledQuery)
            ->whereDate('date', '>', now()->toDateString())
            ->exists();

        $nextScheduledDate = (clone $scheduledQuery)
            ->whereDate('date', '>', now()->toDateString())
            ->orderBy('date')
            ->value('date');

        if ($nextScheduledDate instanceof Carbon) {
            $nextScheduledDate = $nextScheduledDate->toDateString();
        }

        $timesScheduled = (clone $scheduledQuery)->count();

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
            'last_recited_at' => $lastHistory?->date?->toDateString(),
            'last_recited_prayer' => $lastHistory?->prayer_name,
            'days_since_last_recitation' => $daysSince,
            'times_scheduled' => $timesScheduled,
            'times_recited' => $timesRecited,
        ];
    }
}
