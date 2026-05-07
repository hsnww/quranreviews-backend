<?php

namespace Tests\Unit;

use App\Services\QuizScoreCalculator;
use Tests\TestCase;

class QuizScoreCalculatorTest extends TestCase
{
    public function test_score_drops_by_half_per_error(): void
    {
        $this->assertSame(99.5, QuizScoreCalculator::score(1));
        $this->assertSame(95.0, QuizScoreCalculator::score(10));
    }

    public function test_score_never_drops_below_zero(): void
    {
        $this->assertSame(0.0, QuizScoreCalculator::score(1000));
    }

    public function test_formula_description_matches_new_weight(): void
    {
        $this->assertSame('max(0, 100 - total_errors * 0.5)', QuizScoreCalculator::formulaDescription());
    }
}
