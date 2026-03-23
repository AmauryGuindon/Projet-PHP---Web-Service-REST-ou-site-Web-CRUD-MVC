<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExternalOddsService;
use Illuminate\Http\JsonResponse;

class ExternalSyncController extends Controller
{
    public function __construct(private readonly ExternalOddsService $externalOddsService) {}

    public function sports(): JsonResponse
    {
        $sports = $this->externalOddsService->fetchSports();

        return response()->json([
            'source' => 'external_api',
            'count'  => count($sports),
            'data'   => $sports,
        ]);
    }
}
