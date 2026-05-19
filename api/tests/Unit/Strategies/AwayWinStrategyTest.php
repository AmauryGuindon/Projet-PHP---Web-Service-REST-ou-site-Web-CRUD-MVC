<?php

namespace Tests\Unit\Strategies;

use App\Models\SportMatch;
use App\Strategies\AwayWinStrategy;
use PHPUnit\Framework\TestCase;

class AwayWinStrategyTest extends TestCase
{
    private AwayWinStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new AwayWinStrategy();
    }

    public function test_away_team_winning_resolves_away_win_prediction_as_won(): void
    {
        $match = new SportMatch(['home_score' => 1, 'away_score' => 4]);

        $this->assertSame('won', $this->strategy->evaluate($match, 'away_win'));
    }

    public function test_away_team_losing_resolves_away_win_prediction_as_lost(): void
    {
        $match = new SportMatch(['home_score' => 3, 'away_score' => 0]);

        $this->assertSame('lost', $this->strategy->evaluate($match, 'away_win'));
    }

    public function test_one_goal_difference_still_counts(): void
    {
        $match = new SportMatch(['home_score' => 1, 'away_score' => 2]);

        $this->assertSame('won', $this->strategy->evaluate($match, 'away_win'));
    }
}
