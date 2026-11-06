<?php

namespace App\Models;

use App\Support\ArabicSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'sort_order'];

    protected static function booted(): void
    {
        static::saving(function (ArticleCategory $category) {
            if (blank($category->slug)) {
                $category->slug = ArabicSlug::make($category->name);
            }
        });
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
