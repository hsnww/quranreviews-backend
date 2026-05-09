<?php

namespace App\Services;

use App\Models\RecitationHistory;
use App\Models\RecitationSession;
use Illuminate\Support\Facades\DB;

class RecitationCompletionService
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';

    public function completeSession(RecitationSession $session): RecitationSession
    {
        return DB::transaction(function () use ($session): RecitationSession {
            $session->loadMissing('segments');

            if ($session->execution_status !== self::STATUS_COMPLETED) {
                $session->update([
                    'execution_status' => self::STATUS_COMPLETED,
                ]);
            }

            foreach ($session->segments as $segment) {
                RecitationHistory::query()->create([
                    'user_id' => $session->user_id,
                    'session_id' => $session->id,
                    'date' => $session->date,
                    'prayer_name' => $session->prayer_name,
                    'rakaa_number' => $segment->rakaa_number,
                    'start_surah' => $segment->start_surah,
                    'start_ayah' => $segment->start_ayah,
                    'end_surah' => $segment->end_surah,
                    'end_ayah' => $segment->end_ayah,
                    'is_from_plan' => true,
                ]);
            }

            return $session->fresh(['segments']);
        });
    }
}
