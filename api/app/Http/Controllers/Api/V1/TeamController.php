<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $teams = Team::query()
            ->with('sport')
            ->when(request()->filled('sport_id'), function ($query): void {
                $query->where('sport_id', request('sport_id'));
            })
            ->when(request()->filled('q'), function ($query): void {
                $query->where('name', 'like', '%'.request('q').'%');
            })
            ->orderBy('name')
            ->paginate(min((int) request('per_page', 15), 50));

        return response()->json($teams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = Team::query()->create($request->validated());

        return response()->json($team->load('sport'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team): JsonResponse
    {
        return response()->json($team->load('sport'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        $team->update($request->validated());

        return response()->json($team->fresh()->load('sport'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team): Response
    {
        $team->delete();

        return response()->noContent();
    }
}
