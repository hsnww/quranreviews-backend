<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecitationSession extends Model
{
    protected $fillable = [
        'plan_id',
        'user_id',
        'date',
        'day_of_week',
        'prayer_name',
        'execution_status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(RecitationPlan::class, 'plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(RecitationSegment::class, 'session_id')->orderBy('order_index');
    }
}
