<?php

namespace Tests\Unit;

use App\Services\AyahExcerptService;
use Tests\TestCase;

class AyahExcerptServiceTest extends TestCase
{
    public function test_excerpt_returns_full_text_for_short_ayah(): void
    {
        $service = app(AyahExcerptService::class);
        $text = 'الحمد لله';

        $this->assertSame($text, $service->excerptSmart($text));
    }

    public function test_excerpt_returns_ellipsis_for_long_ayah(): void
    {
        $service = app(AyahExcerptService::class);
        $text = 'هذا نص اختبار يحتوي على كلمات كثيرة جدا للتأكد من القص';

        $this->assertSame('هذا نص اختبار يحتوي…', $service->excerptSmart($text));
    }

    public function test_excerpt_normalizes_multiple_spaces(): void
    {
        $service = app(AyahExcerptService::class);
        $text = '  هذا   نص   فيه   فراغات  ';

        $this->assertSame('هذا نص فيه فراغات', $service->excerptSmart($text));
    }
}
