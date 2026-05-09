<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecitationHistory extends Model
{
    protected $table = 'recitation_history';

    protected $fillable = [
        'user_id',
        'session_id',
        'date',
        'prayer_name',
        'rakaa_number',
        'start_surah',
        'start_ayah',
        'end_surah',
        'end_ayah',
        'is_from_plan',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_from_plan' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(RecitationSession::class, 'session_id');
    }
}
