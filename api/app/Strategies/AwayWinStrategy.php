<?php

namespace App\Strategies;

use App\Models\SportMatch;

class AwayWinStrategy implements BetOutcomeStrategy
{
    public function evaluate(SportMatch $match, string $predictedOutcome): string
    {
        $actualOutcome = $match->home_score > $match->away_score ? 'home_win'
            : ($match->home_score < $match->away_score ? 'away_win' : 'draw');

        return $predictedOutcome === $actualOutcome ? 'won' : 'lost';
    }
}
