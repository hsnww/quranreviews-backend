<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizSession extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'status',
        'juz_ids',
        'verses_per_card',
        'ensure_juz_coverage',
        'requested_card_count',
        'actual_card_count',
        'score',
        'total_errors',
        'score_formula',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'juz_ids' => 'array',
            'ensure_juz_coverage' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(QuizSessionCard::class)->orderBy('order_index');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
