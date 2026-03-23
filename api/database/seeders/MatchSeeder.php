<?php

namespace Database\Seeders;

use App\Models\Sport;
use App\Models\SportMatch;
use App\Models\Team;
use Illuminate\Database\Seeder;

class MatchSeeder extends Seeder
{
    public function run(): void
    {
        $sports = Sport::all();

        foreach ($sports as $sport) {
            $teams = Team::where('sport_id', $sport->id)->get();
            if ($teams->count() < 2) continue;

            // Match 1: teams[0] vs teams[1]
            SportMatch::create([
                'sport_id'     => $sport->id,
                'home_team_id' => $teams[0]->id,
                'away_team_id' => $teams[1]->id,
                'starts_at'    => now()->addDays(rand(1, 7)),
                'status'       => 'scheduled',
                'home_score'   => null,
                'away_score'   => null,
            ]);

            // Match 2: teams[2] vs teams[3]
            SportMatch::create([
                'sport_id'     => $sport->id,
                'home_team_id' => $teams[2]->id,
                'away_team_id' => $teams[3]->id,
                'starts_at'    => now()->addDays(rand(8, 14)),
                'status'       => 'scheduled',
                'home_score'   => null,
                'away_score'   => null,
            ]);
        }
    }
}
