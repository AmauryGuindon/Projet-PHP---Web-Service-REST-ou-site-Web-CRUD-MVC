<?php

namespace Tests\Unit\Strategies;

use App\Models\SportMatch;
use App\Strategies\DrawStrategy;
use PHPUnit\Framework\TestCase;

class DrawStrategyTest extends TestCase
{
    private DrawStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new DrawStrategy();
    }

    public function test_actual_draw_resolves_draw_prediction_as_won(): void
    {
        $match = new SportMatch(['home_score' => 2, 'away_score' => 2]);

        $this->assertSame('won', $this->strategy->evaluate($match, 'draw'));
    }

    public function test_home_win_resolves_draw_prediction_as_lost(): void
    {
        $match = new SportMatch(['home_score' => 2, 'away_score' => 1]);

        $this->assertSame('lost', $this->strategy->evaluate($match, 'draw'));
    }

    public function test_away_win_resolves_draw_prediction_as_lost(): void
    {
        $match = new SportMatch(['home_score' => 1, 'away_score' => 2]);

        $this->assertSame('lost', $this->strategy->evaluate($match, 'draw'));
    }
}
