<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SportSeeder extends Seeder
{
    public function run(): void
    {
        $sports = [
            'Football', 'Basketball', 'Tennis', 'Rugby',
            'Handball', 'Volleyball', 'Hockey sur glace', 'Cyclisme',
        ];

        foreach ($sports as $name) {
            Sport::create([
                'name'      => $name,
                'slug'      => Str::slug($name),
                'is_active' => true,
            ]);
        }
    }
}
