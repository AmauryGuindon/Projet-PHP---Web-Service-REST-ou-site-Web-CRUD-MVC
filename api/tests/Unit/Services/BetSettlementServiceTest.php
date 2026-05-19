<?php

namespace Tests\Unit\Services;

use App\Models\SportMatch;
use App\Strategies\HomeWinStrategy;
use PHPUnit\Framework\TestCase;

class BetSettlementServiceTest extends TestCase
{
    public function test_strategy_evaluates_home_win_correctly(): void
    {
        $strategy = new HomeWinStrategy();
        $match = new SportMatch(['home_score' => 2, 'away_score' => 0]);

        $this->assertSame('won', $strategy->evaluate($match, 'home_win'));
        $this->assertSame('lost', $strategy->evaluate($match, 'draw'));
        $this->assertSame('lost', $strategy->evaluate($match, 'away_win'));
    }

    public function test_strategy_evaluates_draw_correctly(): void
    {
        $strategy = new HomeWinStrategy();
        $match = new SportMatch(['home_score' => 1, 'away_score' => 1]);

        $this->assertSame('won', $strategy->evaluate($match, 'draw'));
        $this->assertSame('lost', $strategy->evaluate($match, 'home_win'));
        $this->assertSame('lost', $strategy->evaluate($match, 'away_win'));
    }

    public function test_strategy_evaluates_away_win_correctly(): void
    {
        $strategy = new HomeWinStrategy();
        $match = new SportMatch(['home_score' => 0, 'away_score' => 3]);

        $this->assertSame('won', $strategy->evaluate($match, 'away_win'));
        $this->assertSame('lost', $strategy->evaluate($match, 'home_win'));
        $this->assertSame('lost', $strategy->evaluate($match, 'draw'));
    }
}
