<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * واجهات عامة (بلا تسجيل دخول) لمدونة الموقع، تستهلكها واجهة Next.js من السيرفر.
 */
class ArticleController extends Controller
{
    private function summary(Article $article): array
    {
        return [
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'cover_image' => $article->coverUrl(),
            'reading_minutes' => $article->reading_minutes,
            'published_at' => $article->published_at?->toIso8601String(),
            'updated_at' => $article->updated_at?->toIso8601String(),
            'category' => $article->category ? [
                'name' => $article->category->name,
                'slug' => $article->category->slug,
            ] : null,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 12), 1), 50);

        $page = Article::published()
            ->with('category')
            ->when($request->query('category'), fn ($q, $slug) =>
                $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $page->getCollection()->map(fn (Article $a) => $this->summary($a))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $article = Article::published()->with('category')->where('slug', $slug)->first();
        if (!$article) {
            return response()->json(['message' => 'المقال غير موجود'], 404);
        }

        $rendered = $article->renderBody();

        $related = Article::published()
            ->with('category')
            ->where('id', '!=', $article->id)
            ->when($article->article_category_id, fn ($q, $id) => $q->where('article_category_id', $id))
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return response()->json([
            'data' => $this->summary($article) + [
                'body_html' => $rendered['html'],
                'toc' => $rendered['toc'],
                'faqs' => array_values(array_filter(
                    $article->faqs ?? [],
                    fn ($f) => filled($f['question'] ?? null) && filled($f['answer'] ?? null)
                )),
                'meta_title' => $article->meta_title ?: $article->title,
                'meta_description' => $article->meta_description ?: $article->excerpt,
                'focus_keyword' => $article->focus_keyword,
                'related' => $related->map(fn (Article $a) => $this->summary($a))->values(),
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = ArticleCategory::query()
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $categories->map(fn (ArticleCategory $c) => [
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'articles_count' => $c->articles_count,
            ])->values(),
        ]);
    }

    /** بيانات خفيفة لخريطة الموقع: روابط المقالات والتصنيفات وتواريخ آخر تعديل */
    public function sitemap(): JsonResponse
    {
        $articles = Article::published()
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at', 'published_at']);

        $categories = ArticleCategory::query()
            ->whereHas('articles', fn ($q) => $q->published())
            ->get(['id', 'slug', 'updated_at']);

        return response()->json([
            'articles' => $articles->map(fn ($a) => [
                'slug' => $a->slug,
                'updated_at' => ($a->updated_at ?? $a->published_at)?->toIso8601String(),
            ])->values(),
            'categories' => $categories->map(fn ($c) => [
                'slug' => $c->slug,
                'updated_at' => $c->updated_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
