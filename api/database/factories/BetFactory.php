<?php

namespace Database\Factories;

use App\Models\SportMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BetFactory extends Factory
{
    public function definition(): array
    {
        $amount    = $this->faker->randomFloat(2, 5, 200);
        $oddsValue = $this->faker->randomFloat(2, 1.10, 5.00);

        return [
            'user_id'           => '1',
            'match_id'          => SportMatch::factory(),
            'amount'            => $amount,
            'predicted_outcome' => $this->faker->randomElement(['home_win', 'draw', 'away_win']),
            'odds_value'        => $oddsValue,
            'potential_gain'    => round($amount * $oddsValue, 2),
            'status'            => $this->faker->randomElement(['pending', 'won', 'lost']),
        ];
    }
}
