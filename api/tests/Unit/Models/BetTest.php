<?php

namespace Tests\Unit\Models;

use App\Models\Bet;
use PHPUnit\Framework\TestCase;

class BetTest extends TestCase
{
    public function test_uses_mongodb_connection(): void
    {
        $bet = new Bet();
        $this->assertSame('mongodb', $bet->getConnectionName());
    }

    public function test_uses_bets_collection(): void
    {
        $bet = new Bet();
        $this->assertSame('bets', $bet->getTable());
    }

    public function test_fillable_contains_all_business_attributes(): void
    {
        $bet = new Bet();
        $expected = [
            'user_id', 'match_id', 'amount', 'predicted_outcome',
            'odds_value', 'potential_gain', 'status',
        ];

        foreach ($expected as $attr) {
            $this->assertContains($attr, $bet->getFillable());
        }
    }

    public function test_numeric_attributes_are_cast_to_float(): void
    {
        $bet = new Bet(['amount' => '50', 'odds_value' => '2.5', 'potential_gain' => '125']);

        $this->assertSame(50.0, $bet->amount);
        $this->assertSame(2.5, $bet->odds_value);
        $this->assertSame(125.0, $bet->potential_gain);
    }

    public function test_potential_gain_equals_amount_times_odds(): void
    {
        $amount = 25.0;
        $odds = 3.4;
        $bet = new Bet([
            'amount' => $amount,
            'odds_value' => $odds,
            'potential_gain' => $amount * $odds,
        ]);

        $this->assertEqualsWithDelta(85.0, $bet->potential_gain, 0.001);
    }
}
