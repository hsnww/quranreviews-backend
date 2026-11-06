<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Support\ArabicSlug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * يضيف تصنيفات المدونة ومقالاتها الأولية من ملفات المحتوى في
 * database/content/articles/*.php (كل ملف يُرجِع مصفوفة: title, slug, category, excerpt,
 * focus_keyword, meta_title, meta_description, faqs, body — والنص Markdown داخل heredoc).
 *
 * آمن للتشغيل مع كل نشر: يضيف الجديد فقط (حسب الرابط) ولا يمسّ ما عُدِّل من لوحة Filament.
 *   php artisan db:seed --class=ArticleSeeder --force
 */
class ArticleSeeder extends Seeder
{
    private const CATEGORIES = [
        ['slug' => 'حفظ-القران', 'name' => 'حفظ القرآن', 'sort_order' => 1,
            'description' => 'طرق حفظ القرآن الكريم وجداوله وبرامجه للكبار والأطفال، وكيف تبدأ وتستمر.'],
        ['slug' => 'مراجعة-القران', 'name' => 'مراجعة القرآن وتثبيته', 'sort_order' => 2,
            'description' => 'المراجعة القريبة والبعيدة والأسبوعية، وجداول ختم المراجعة وتثبيت المحفوظ.'],
        ['slug' => 'التحفيظ-والحلقات', 'name' => 'التحفيظ والحلقات', 'sort_order' => 3,
            'description' => 'تحفيظ القرآن عن بعد وفي الحلقات، للنساء والكبار، ومتابعة المعلم لطلابه.'],
        ['slug' => 'الامام-والمسجد', 'name' => 'الإمام والمسجد', 'sort_order' => 4,
            'description' => 'تنظيم قراءة الإمام في الصلوات الجهرية، ومكانة المسجد ومهام إمامه.'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            ArticleCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }

        $files = File::glob(database_path('content/articles/*.php'));
        sort($files);

        $added = 0;
        foreach ($files as $file) {
            $meta = require $file;
            $body = trim((string) ($meta["body"] ?? ""));
            $slug = ArabicSlug::make($meta['slug'] ?? $meta['title'] ?? basename($file, '.php'));

            if (Article::where('slug', $slug)->exists()) {
                continue;
            }

            $categoryId = isset($meta['category'])
                ? ArticleCategory::where('slug', $meta['category'])->value('id')
                : null;

            Article::create([
                'title' => $meta['title'],
                'slug' => $slug,
                'article_category_id' => $categoryId,
                'excerpt' => $meta['excerpt'] ?? null,
                'body' => $body,
                'faqs' => $meta['faqs'] ?? null,
                'meta_title' => $meta['meta_title'] ?? null,
                'meta_description' => $meta['meta_description'] ?? null,
                'focus_keyword' => $meta['focus_keyword'] ?? null,
                'status' => $meta['status'] ?? 'published',
                'published_at' => $meta['published_at'] ?? now(),
            ]);
            $added++;
        }

        $this->command?->info("ArticleSeeder: أُضيف {$added} مقال جديد (من أصل " . count($files) . ' ملف).');
    }

}
