<?php

namespace Database\Factories;

use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class SportMatchFactory extends Factory
{
    public function definition(): array
    {
        $sport = Sport::factory()->create();
        return [
            'sport_id'     => $sport->id,
            'home_team_id' => Team::factory()->create(['sport_id' => $sport->id])->id,
            'away_team_id' => Team::factory()->create(['sport_id' => $sport->id])->id,
            'starts_at'    => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'status'       => 'scheduled',
            'home_score'   => null,
            'away_score'   => null,
        ];
    }
}
