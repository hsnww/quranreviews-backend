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
        'is_revision',
        'date',
        'is_weekly_review',
    ];

    protected $casts = [
        'is_revision' => 'boolean',
        'is_weekly_review' => 'boolean',
        'date' => 'date',
    ];

    private const ARABIC_WEEKDAYS = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

    /** تسمية نوع الصف كما تظهر في الواجهة والتصدير */
    public function lessonLabel(): string
    {
        if ($this->is_weekly_review) {
            return 'مراجعة الأسبوع';
        }
        if ($this->type !== 'new') {
            return 'مراجعة بعيدة';
        }
        return $this->is_revision ? 'مراجعة السابق' : 'جديد';
    }

    /** مفتاح نوع الصف للتلوين: weekly | review | revision | new */
    public function lessonKind(): string
    {
        if ($this->is_weekly_review) {
            return 'weekly';
        }
        if ($this->type !== 'new') {
            return 'review';
        }
        return $this->is_revision ? 'revision' : 'new';
    }

    /** «الأحد 8/11/2026» أو null للخطط القديمة بلا تاريخ */
    public function dateWithWeekday(): ?string
    {
        if (!$this->date) {
            return null;
        }
        return self::ARABIC_WEEKDAYS[$this->date->dayOfWeek] . ' ' . $this->date->format('j/n/Y');
    }

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
