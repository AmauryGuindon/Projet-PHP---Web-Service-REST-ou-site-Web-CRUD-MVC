<?php

namespace Database\Seeders;

use App\Models\Bet;
use App\Models\Odd;
use App\Models\SportMatch;
use App\Models\User;
use Illuminate\Database\Seeder;

class BetSeeder extends Seeder
{
    public function run(): void
    {
        $users   = User::where('role', 'user')->get();
        $matches = SportMatch::all();
        $outcomes = ['home_win', 'draw', 'away_win'];

        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $match = $matches->random();
                $odd   = Odd::where('match_id', $match->id)->first();

                if (!$odd) continue;

                $outcome   = $outcomes[array_rand($outcomes)];
                $amount    = round(rand(10, 200), 2);
                $oddsValue = match ($outcome) {
                    'home_win' => $odd->home_win,
                    'draw'     => $odd->draw,
                    'away_win' => $odd->away_win,
                };

                Bet::create([
                    'user_id'           => $user->id,
                    'match_id'          => $match->id,
                    'amount'            => $amount,
                    'predicted_outcome' => $outcome,
                    'odds_value'        => $oddsValue,
                    'potential_gain'    => round($amount * $oddsValue, 2),
                    'status'            => collect(['pending', 'won', 'lost'])->random(),
                ]);
            }
        }
    }
}
