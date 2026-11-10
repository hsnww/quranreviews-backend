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
        'prayer_tags',
    ];

    /** الصلوات الجهرية المسموح وسم المقطع بها. */
    public const PRAYER_TAGS = ['fajr', 'maghrib', 'isha'];

    protected $casts = [
        'prayer_tags' => 'array',
    ];

    /**
     * يزيل التكرار ويرتّب الوسوم بترتيب الصلوات.
     *
     * @param  array<int, string>|null  $tags
     * @return array<int, string>
     */
    public static function normalizePrayerTags(?array $tags): array
    {
        return array_values(array_intersect(self::PRAYER_TAGS, $tags ?? []));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
