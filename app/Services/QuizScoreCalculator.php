<?php

namespace App\Services;

/**
 * Server-defined quiz score; keep in sync with API docs consumed by the frontend.
 */
final class QuizScoreCalculator
{
    public const ERROR_WEIGHT = 2;

    public static function formulaDescription(): string
    {
        return sprintf('max(0, 100 - total_errors * %d)', self::ERROR_WEIGHT);
    }

    public static function score(int $totalErrors): int
    {
        return max(0, 100 - $totalErrors * self::ERROR_WEIGHT);
    }
}
