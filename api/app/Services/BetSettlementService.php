<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\SportMatch;
use App\Strategies\HomeWinStrategy;

class BetSettlementService
{
    private HomeWinStrategy $strategy;

    public function __construct()
    {
        $this->strategy = new HomeWinStrategy();
    }

    public function settleMatch(SportMatch $match): int
    {
        $bets = Bet::query()
            ->where('match_id', (string) $match->id)
            ->where('status', 'pending')
            ->get();

        foreach ($bets as $bet) {
            $result = $this->strategy->evaluate($match, $bet->predicted_outcome);
            $bet->update(['status' => $result]);
        }

        return $bets->count();
    }
}
