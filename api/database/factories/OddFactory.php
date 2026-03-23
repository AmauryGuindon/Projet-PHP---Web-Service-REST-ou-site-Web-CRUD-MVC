<?php

namespace Database\Factories;

use App\Models\SportMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class OddFactory extends Factory
{
    public function definition(): array
    {
        return [
            'match_id'  => SportMatch::factory(),
            'home_win'  => $this->faker->randomFloat(2, 1.10, 5.00),
            'draw'      => $this->faker->randomFloat(2, 2.50, 4.50),
            'away_win'  => $this->faker->randomFloat(2, 1.10, 5.00),
            'bookmaker' => $this->faker->randomElement(['BetFrance', 'Unibet', 'PMU', 'Winamax']),
            'source'    => 'internal',
        ];
    }
}
