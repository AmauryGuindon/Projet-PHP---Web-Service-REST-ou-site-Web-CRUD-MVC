<?php

namespace Database\Factories;

use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sport_id'   => Sport::factory(),
            'name'       => $this->faker->unique()->company(),
            'short_name' => strtoupper($this->faker->lexify('???')),
            'country'    => $this->faker->country(),
            'logo_url'   => null,
        ];
    }
}
