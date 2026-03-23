<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBetRequest;
use App\Http\Requests\UpdateBetRequest;
use App\Models\Bet;
use App\Models\Odd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class BetController extends Controller
{
    public function index(): JsonResponse
    {
        $bets = Bet::query()
            ->where('user_id', request()->user()->id)
            ->when(request()->filled('status'), fn($q) => $q->where('status', request('status')))
            ->with('match')
            ->orderByDesc('created_at')
            ->paginate(min((int) request('per_page', 15), 50));

        return response()->json($bets);
    }

    public function store(StoreBetRequest $request): JsonResponse
    {
        $data = $request->validated();

        $odd = Odd::query()->where('match_id', $data['match_id'])->latest()->first();
        if (!$odd) {
            throw ValidationException::withMessages([
                'match_id' => ['Aucune cote disponible pour ce match.'],
            ]);
        }

        $oddsValue = match ($data['predicted_outcome']) {
            'home_win' => $odd->home_win,
            'draw'     => $odd->draw,
            'away_win' => $odd->away_win,
        };

        $bet = Bet::query()->create([
            ...$data,
            'user_id'        => $request->user()->id,
            'odds_value'     => $oddsValue,
            'potential_gain' => round($data['amount'] * $oddsValue, 2),
            'status'         => 'pending',
        ]);

        return response()->json($bet->load('match'), 201, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function show(Bet $bet): JsonResponse
    {
        $this->authorizeOwner($bet);
        return response()->json($bet->load('match'));
    }

    public function update(UpdateBetRequest $request, Bet $bet): JsonResponse
    {
        abort_if($bet->status !== 'pending', 422, 'Impossible de modifier un pari déjà traité.');
        $this->authorizeOwner($bet);
        $bet->update($request->validated());
        return response()->json($bet->fresh()->load('match'));
    }

    public function destroy(Bet $bet): Response
    {
        abort_if($bet->status !== 'pending', 422, 'Impossible d\'annuler un pari déjà traité.');
        $this->authorizeOwner($bet);
        $bet->update(['status' => 'cancelled']);
        return response()->noContent();
    }

    private function authorizeOwner(Bet $bet): void
    {
        $user = request()->user();
        if ((string) $bet->user_id !== (string) $user->id && $user->role !== 'admin') {
            abort(403, 'Interdit.');
        }
    }
}
