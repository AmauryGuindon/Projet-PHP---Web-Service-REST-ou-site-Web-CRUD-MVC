<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SportFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Football', 'Basketball', 'Tennis', 'Rugby', 'Handball',
            'Volleyball', 'Baseball', 'Hockey', 'Cycling', 'Boxing',
        ]);

        return [
            'name'      => $name,
            'slug'      => \Illuminate\Support\Str::slug($name),
            'is_active' => true,
        ];
    }
}
