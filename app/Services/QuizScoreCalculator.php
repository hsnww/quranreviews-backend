<?php

namespace App\Services;

/**
 * Server-defined quiz score; keep in sync with API docs consumed by the frontend.
 */
final class QuizScoreCalculator
{
    public const ERROR_WEIGHT = 0.5;

    public static function formulaDescription(): string
    {
        return sprintf('max(0, 100 - total_errors * %s)', self::formatFloat(self::ERROR_WEIGHT));
    }

    public static function score(int $totalErrors): float
    {
        return max(0.0, round(100 - $totalErrors * self::ERROR_WEIGHT, 1));
    }

    private static function formatFloat(float $value): string
    {
        if ((int) $value === $value) {
            return (string) ((int) $value);
        }

        return rtrim(rtrim(sprintf('%.2f', $value), '0'), '.');
    }
}
