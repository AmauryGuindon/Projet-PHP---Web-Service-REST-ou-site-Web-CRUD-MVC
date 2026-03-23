<?php

namespace App\Strategies;

use App\Models\SportMatch;

interface BetOutcomeStrategy
{
    public function evaluate(SportMatch $match, string $predictedOutcome): string;
}
