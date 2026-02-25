<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSportRequest;
use App\Http\Requests\UpdateSportRequest;
use App\Models\Sport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $sports = Sport::query()
            ->when(request()->filled('q'), function ($query): void {
                $query->where('name', 'like', '%'.request('q').'%');
            })
            ->when(request()->filled('is_active'), function ($query): void {
                $query->where('is_active', filter_var(request('is_active'), FILTER_VALIDATE_BOOL));
            })
            ->orderBy('name')
            ->paginate(min((int) request('per_page', 15), 50));

        return response()->json($sports);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSportRequest $request): JsonResponse
    {
        $sport = Sport::query()->create($request->validated());

        return response()->json($sport, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sport $sport): JsonResponse
    {
        return response()->json($sport);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSportRequest $request, Sport $sport): JsonResponse
    {
        $sport->update($request->validated());

        return response()->json($sport->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sport $sport): Response
    {
        $sport->delete();

        return response()->noContent();
    }
}
