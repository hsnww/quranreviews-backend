<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** خطة حفظ (new) أو مراجعة بعيدة (review) للطالب؛ أيامها في review_plans */
class StudyPlan extends Model
{
    protected $fillable = [
        'student_id',
        'type',
        'title',
        'start_date',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(ReviewPlan::class);
    }

    /** الاسم الظاهر: الاسم الذي اختاره الطالب، وإلا اسم النوع */
    public function displayTitle(): string
    {
        if (filled($this->title)) {
            return $this->title;
        }
        return $this->type === 'new' ? 'خطة الحفظ' : 'جدول المراجعة البعيدة';
    }

    /** ملخص الخطة للواجهة (عدد الأيام لا الصفوف، وآخر تاريخ فيها) */
    public function toSummary(): array
    {
        $endDate = $this->days()->max('date');

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'display_title' => $this->displayTitle(),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $endDate ? substr((string) $endDate, 0, 10) : null,
            'days_count' => (int) $this->days()->distinct()->count('day_number'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
