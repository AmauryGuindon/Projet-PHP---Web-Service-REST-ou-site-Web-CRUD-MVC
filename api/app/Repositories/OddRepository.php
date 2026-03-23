<?php

namespace App\Repositories;

use App\Models\Odd;

class OddRepository
{
    public function latestForMatch(string $matchId): ?Odd
    {
        return Odd::query()->where('match_id', $matchId)->latest()->first();
    }

    public function create(array $data): Odd
    {
        return Odd::query()->create($data);
    }
}
