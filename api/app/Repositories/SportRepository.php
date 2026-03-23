<?php

namespace App\Repositories;

use App\Models\Sport;
use Illuminate\Pagination\LengthAwarePaginator;

class SportRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Sport::query()
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->orderBy('name')
            ->paginate(min((int) ($filters['per_page'] ?? 15), 50));
    }
}
