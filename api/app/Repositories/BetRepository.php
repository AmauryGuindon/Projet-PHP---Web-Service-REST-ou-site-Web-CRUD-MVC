<?php

namespace App\Repositories;

use App\Models\Bet;
use Illuminate\Pagination\LengthAwarePaginator;

class BetRepository
{
    public function forUser(mixed $userId, array $filters = []): LengthAwarePaginator
    {
        return Bet::query()
            ->where('user_id', $userId)
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->with('match')
            ->orderByDesc('created_at')
            ->paginate(min((int) ($filters['per_page'] ?? 15), 50));
    }

    public function create(array $data): Bet
    {
        return Bet::query()->create($data);
    }

    public function update(Bet $bet, array $data): Bet
    {
        $bet->update($data);
        return $bet->fresh();
    }
}
