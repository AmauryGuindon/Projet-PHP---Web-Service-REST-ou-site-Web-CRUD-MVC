<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOddRequest;
use App\Http\Requests\UpdateOddRequest;
use App\Models\Odd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OddController extends Controller
{
    public function index(): JsonResponse
    {
        $odds = Odd::query()
            ->when(request()->filled('match_id'), fn($q) => $q->where('match_id', request('match_id')))
            ->with('match')
            ->orderByDesc('created_at')
            ->paginate(min((int) request('per_page', 15), 50));

        return response()->json($odds);
    }

    public function store(StoreOddRequest $request): JsonResponse
    {
        $odd = Odd::query()->create($request->validated());
        return response()->json($odd->load('match'), 201);
    }

    public function show(Odd $odd): JsonResponse
    {
        return response()->json($odd->load('match'));
    }

    public function update(UpdateOddRequest $request, Odd $odd): JsonResponse
    {
        $odd->update($request->validated());
        return response()->json($odd->fresh()->load('match'));
    }

    public function destroy(Odd $odd): Response
    {
        $odd->delete();
        return response()->noContent();
    }
}
