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

            $matchups = [
                [0, 1, 1, 'scheduled'],
                [2, 3, 3, 'scheduled'],
                [4, 5, 7, 'scheduled'],
                [1, 2, -2, 'finished'],
                [3, 4, -5, 'finished'],
            ];

            foreach ($matchups as [$hi, $ai, $days, $status]) {
                if (!isset($teams[$hi]) || !isset($teams[$ai])) continue;

                $homeScore = null;
                $awayScore = null;
                if ($status === 'finished') {
                    $homeScore = rand(0, 3);
                    $awayScore = rand(0, 3);
                }

                SportMatch::create([
                    'sport_id'     => $sport->id,
                    'home_team_id' => $teams[$hi]->id,
                    'away_team_id' => $teams[$ai]->id,
                    'starts_at'    => now()->addDays($days),
                    'status'       => $status,
                    'home_score'   => $homeScore,
                    'away_score'   => $awayScore,
                ]);
            }
        }
    }
}
