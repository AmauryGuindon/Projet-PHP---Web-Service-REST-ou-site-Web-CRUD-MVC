<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSportMatchRequest;
use App\Http\Requests\UpdateSportMatchRequest;
use App\Models\Sport;
use App\Models\SportMatch;
use App\Models\Team;
use App\Services\BetSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class SportMatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $matches = SportMatch::query()
            ->when(request()->filled('sport_id'), function ($query): void {
                $query->where('sport_id', request('sport_id'));
            })
            ->when(request()->filled('status'), function ($query): void {
                $query->where('status', request('status'));
            })
            ->orderByDesc('starts_at')
            ->paginate(min((int) request('per_page', 15), 50));

        $this->injectRelations($matches->getCollection());

        return response()->json($matches);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSportMatchRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $this->assertTeamsBelongToSport($payload);

        $match = SportMatch::query()->create($payload);

        return response()->json($match->load(['sport', 'homeTeam', 'awayTeam']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(SportMatch $matchItem): JsonResponse
    {
        return response()->json($matchItem->load(['sport', 'homeTeam', 'awayTeam']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSportMatchRequest $request, SportMatch $matchItem): JsonResponse
    {
        $payload = $request->validated();

        $merged = array_merge([
            'sport_id' => $matchItem->sport_id,
            'home_team_id' => $matchItem->home_team_id,
            'away_team_id' => $matchItem->away_team_id,
        ], $payload);

        $this->assertTeamsBelongToSport($merged);

        $matchItem->update($payload);

        return response()->json($matchItem->fresh()->load(['sport', 'homeTeam', 'awayTeam']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SportMatch $matchItem): Response
    {
        $matchItem->delete();

        return response()->noContent();
    }

    public function settle(SportMatch $matchItem, BetSettlementService $settlementService): JsonResponse
    {
        abort_if($matchItem->status === 'finished', 422, 'Match déjà résolu.');
        abort_if(
            is_null($matchItem->home_score) || is_null($matchItem->away_score),
            422,
            'Les scores doivent être définis avant la résolution.'
        );

        $matchItem->update(['status' => 'finished']);
        $count = $settlementService->settleMatch($matchItem);

        return response()->json([
            'message'       => 'Match résolu.',
            'bets_resolved' => $count,
            'match'         => $matchItem->fresh(),
        ]);
    }

    private function injectRelations(\Illuminate\Support\Collection $collection): void
    {
        $sportIds   = $collection->pluck('sport_id')->unique()->filter()->values()->all();
        $homeIds    = $collection->pluck('home_team_id')->unique()->filter()->values()->all();
        $awayIds    = $collection->pluck('away_team_id')->unique()->filter()->values()->all();
        $teamIds    = collect(array_merge($homeIds, $awayIds))->unique()->values()->all();

        $sports = collect($sportIds)->mapWithKeys(fn($id) => [(string) $id => Sport::find($id)])->filter();
        $teams  = collect($teamIds)->mapWithKeys(fn($id) => [(string) $id => Team::find($id)])->filter();

        $collection->each(function ($match) use ($sports, $teams): void {
            $match->setRelation('sport',    $sports->get((string) $match->sport_id));
            $match->setRelation('homeTeam', $teams->get((string) $match->home_team_id));
            $match->setRelation('awayTeam', $teams->get((string) $match->away_team_id));
        });
    }

    private function assertTeamsBelongToSport(array $payload): void
    {
        $homeTeam = Team::query()->findOrFail($payload['home_team_id']);
        $awayTeam = Team::query()->findOrFail($payload['away_team_id']);

        if (
            (string) $homeTeam->sport_id !== (string) $payload['sport_id'] ||
            (string) $awayTeam->sport_id !== (string) $payload['sport_id']
        ) {
            throw ValidationException::withMessages([
                'sport_id' => ['home_team_id and away_team_id must belong to sport_id.'],
            ]);
        }
    }
}
