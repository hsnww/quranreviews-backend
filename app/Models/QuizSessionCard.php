<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizSessionCard extends Model
{
    protected $fillable = [
        'quiz_session_id',
        'order_index',
        'sora_number',
        'jozo',
        'verse_ids',
        'mistake_count',
    ];

    protected function casts(): array
    {
        return [
            'verse_ids' => 'array',
        ];
    }

    public function quizSession(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class);
    }
}
