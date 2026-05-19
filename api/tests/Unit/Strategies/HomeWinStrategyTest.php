<?php

namespace Tests\Unit\Strategies;

use App\Models\SportMatch;
use App\Strategies\HomeWinStrategy;
use PHPUnit\Framework\TestCase;

class HomeWinStrategyTest extends TestCase
{
    private HomeWinStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new HomeWinStrategy();
    }

    public function test_predicting_home_win_with_home_winning_returns_won(): void
    {
        $match = new SportMatch(['home_score' => 3, 'away_score' => 1]);

        $this->assertSame('won', $this->strategy->evaluate($match, 'home_win'));
    }

    public function test_predicting_home_win_with_away_winning_returns_lost(): void
    {
        $match = new SportMatch(['home_score' => 0, 'away_score' => 2]);

        $this->assertSame('lost', $this->strategy->evaluate($match, 'home_win'));
    }

    public function test_predicting_home_win_on_draw_returns_lost(): void
    {
        $match = new SportMatch(['home_score' => 1, 'away_score' => 1]);

        $this->assertSame('lost', $this->strategy->evaluate($match, 'home_win'));
    }

    public function test_predicting_draw_on_draw_returns_won(): void
    {
        $match = new SportMatch(['home_score' => 2, 'away_score' => 2]);

        $this->assertSame('won', $this->strategy->evaluate($match, 'draw'));
    }

    public function test_predicting_away_win_with_away_winning_returns_won(): void
    {
        $match = new SportMatch(['home_score' => 0, 'away_score' => 3]);

        $this->assertSame('won', $this->strategy->evaluate($match, 'away_win'));
    }

    public function test_zero_zero_match_is_a_draw(): void
    {
        $match = new SportMatch(['home_score' => 0, 'away_score' => 0]);

        $this->assertSame('won', $this->strategy->evaluate($match, 'draw'));
        $this->assertSame('lost', $this->strategy->evaluate($match, 'home_win'));
        $this->assertSame('lost', $this->strategy->evaluate($match, 'away_win'));
    }
}
