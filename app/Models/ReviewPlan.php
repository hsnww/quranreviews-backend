<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewPlan extends Model
{
    protected $fillable = [
        'student_id',
        'day_number',
        'from_verse_id',
        'to_verse_id',
        'type',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromVerse(): BelongsTo
    {
        return $this->belongsTo(QuranVerse::class, 'from_verse_id');
    }

    public function toVerse(): BelongsTo
    {
        return $this->belongsTo(QuranVerse::class, 'to_verse_id');
    }
}
