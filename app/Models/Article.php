<?php

namespace App\Models;

use App\Support\ArabicSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Article extends Model
{
    protected $fillable = [
        'article_category_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image',
        'faqs',
        'meta_title',
        'meta_description',
        'focus_keyword',
        'status',
        'published_at',
        'reading_minutes',
    ];

    protected $casts = [
        'faqs' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Article $article) {
            if (blank($article->slug)) {
                $article->slug = ArabicSlug::make($article->title);
            }
            // نحو 180 كلمة عربية في الدقيقة
            $words = count(preg_split('/\s+/u', trim(strip_tags((string) $article->body)), -1, PREG_SPLIT_NO_EMPTY));
            $article->reading_minutes = max(1, (int) ceil($words / 180));

            if ($article->status === 'published' && !$article->published_at) {
                $article->published_at = now();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    /** المنشور فعلًا (حالته منشور وتاريخ نشره حلّ) */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function coverUrl(): ?string
    {
        return $this->cover_image ? Storage::disk('public')->url($this->cover_image) : null;
    }

    /**
     * يحوّل نص Markdown إلى HTML آمن (يُحذف أي HTML خام) ويضيف معرّفات للعناوين
     * h2/h3 لفهرس المحتويات.
     *
     * @return array{html: string, toc: array<int, array{id: string, text: string, level: int}>}
     */
    public function renderBody(): array
    {
        $html = Str::markdown((string) $this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $toc = [];
        $i = 0;
        $html = preg_replace_callback('/<h([23])>(.*?)<\/h\1>/su', function ($m) use (&$toc, &$i) {
            $id = 'section-' . (++$i);
            $toc[] = ['id' => $id, 'text' => trim(strip_tags($m[2])), 'level' => (int) $m[1]];
            return "<h{$m[1]} id=\"{$id}\">{$m[2]}</h{$m[1]}>";
        }, $html);

        return ['html' => $html, 'toc' => $toc];
    }
}
