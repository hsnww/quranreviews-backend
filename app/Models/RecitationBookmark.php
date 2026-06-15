<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecitationBookmark extends Model
{
    protected $fillable = [
        'user_id',
        'start_surah',
        'start_ayah',
        'end_surah',
        'end_ayah',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
