<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecitationSegment extends Model
{
    protected $fillable = [
        'session_id',
        'rakaa_number',
        'start_surah',
        'start_ayah',
        'end_surah',
        'end_ayah',
        'order_index',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(RecitationSession::class, 'session_id');
    }
}
