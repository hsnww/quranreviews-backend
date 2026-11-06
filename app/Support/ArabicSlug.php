<?php

namespace App\Support;

/**
 * رابط (slug) عربي مقروء: «جدول حفظ القرآن الكريم» → «جدول-حفظ-القرآن-الكريم».
 * Str::slug لا يصلح هنا لأنه يحوّل الحروف العربية إلى لاتينية.
 */
class ArabicSlug
{
    public static function make(string $text): string
    {
        $text = trim($text);
        // إزالة التشكيل والتطويل
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text);
        // كل ما ليس حرفًا أو رقمًا يصبح فاصلًا
        $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
        $text = trim($text, '-');

        return mb_strtolower(mb_substr($text, 0, 150));
    }
}
