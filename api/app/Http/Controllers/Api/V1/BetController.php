<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBetRequest;
use App\Http\Requests\UpdateBetRequest;
use App\Models\Bet;
use App\Repositories\BetRepository;
use App\Repositories\OddRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class BetController extends Controller
{
    public function __construct(
        private readonly BetRepository $bets,
        private readonly OddRepository $odds
    ) {}

    public function index(): JsonResponse
    {
        $bets = $this->bets->forUser(
            request()->user()->id,
            request()->only(['status', 'per_page'])
        );

        return response()->json($bets);
    }

    #[OA\Post(
        path: '/bets',
        summary: 'Placer un pari',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['match_id', 'amount', 'predicted_outcome'],
                properties: [
                    new OA\Property(property: 'match_id', type: 'string'),
                    new OA\Property(property: 'amount', type: 'number', example: 50),
                    new OA\Property(property: 'predicted_outcome', type: 'string', enum: ['home_win', 'draw', 'away_win']),
                ]
            )
        ),
        tags: ['Bets'],
        responses: [
            new OA\Response(response: 201, description: 'Pari créé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreBetRequest $request): JsonResponse
    {
        $data = $request->validated();

        $odd = $this->odds->latestForMatch($data['match_id']);
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

        $bet = $this->bets->create([
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
