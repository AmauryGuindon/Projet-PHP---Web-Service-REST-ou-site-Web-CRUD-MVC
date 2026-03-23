<?php

namespace Database\Seeders;

use App\Models\Odd;
use App\Models\SportMatch;
use Illuminate\Database\Seeder;

class OddSeeder extends Seeder
{
    public function run(): void
    {
        $matches = SportMatch::all();

        foreach ($matches as $match) {
            Odd::create([
                'match_id'  => $match->id,
                'home_win'  => round(rand(110, 300) / 100, 2),
                'draw'      => round(rand(280, 400) / 100, 2),
                'away_win'  => round(rand(150, 450) / 100, 2),
                'bookmaker' => collect(['Winamax', 'Unibet', 'PMU', 'BetFrance'])->random(),
                'source'    => 'internal',
            ]);
        }
    }
}
