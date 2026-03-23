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
            ['name' => 'Football', 'slug' => 'football'],
            ['name' => 'Basketball', 'slug' => 'basketball'],
            ['name' => 'Rugby', 'slug' => 'rugby'],
            ['name' => 'Tennis', 'slug' => 'tennis'],
            ['name' => 'Handball', 'slug' => 'handball'],
        ];

        foreach ($sports as $sport) {
            Sport::create([
                'name'      => $sport['name'],
                'slug'      => $sport['slug'],
                'is_active' => true,
            ]);
        }
    }
}
