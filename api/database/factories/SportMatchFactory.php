<?php

namespace Database\Factories;

use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SportMatch>
 */
class SportMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sport_id' => Sport::factory(),
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'starts_at' => now()->addDays(2),
            'status' => 'scheduled',
            'home_score' => null,
            'away_score' => null,
        ];
    }
}
