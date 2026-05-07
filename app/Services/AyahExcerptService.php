<?php

namespace App\Services;

final class AyahExcerptService
{
    /**
     * Return a smart excerpt from the ayah text using first words.
     * Adds ellipsis only when text is longer than the selected word count.
     */
    public function excerptSmart(string $ayahText, int $maxWords = 4): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($ayahText)) ?? '';
        if ($normalized === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) <= $maxWords) {
            return $normalized;
        }

        $slice = array_slice($words, 0, max(1, $maxWords));

        return implode(' ', $slice).'…';
    }
}
