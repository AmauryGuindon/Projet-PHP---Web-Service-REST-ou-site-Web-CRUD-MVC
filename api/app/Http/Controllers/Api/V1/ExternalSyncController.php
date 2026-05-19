<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExternalOddsService;
use App\Services\MatchSyncService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ExternalSyncController extends Controller
{
    public function __construct(
        private readonly ExternalOddsService $externalOddsService,
        private readonly MatchSyncService $matchSync,
    ) {}

    public function sports(): JsonResponse
    {
        $sports = $this->externalOddsService->fetchSports();

        return response()->json([
            'source' => 'external_api',
            'count'  => count($sports),
            'data'   => $sports,
        ]);
    }

    #[OA\Post(
        path: '/external/sync-matches',
        summary: 'Synchronise les rencontres + cotes depuis The Odds API (admin)',
        security: [['bearerAuth' => []]],
        tags: ['External'],
        responses: [
            new OA\Response(response: 200, description: 'Statistiques de synchronisation'),
            new OA\Response(response: 403, description: 'Réservé aux admins'),
        ]
    )]
    public function syncMatches(): JsonResponse
    {
        $stats = $this->matchSync->syncAll();

        return response()->json([
            'source'  => 'the_odds_api',
            'message' => 'Synchronisation terminée.',
            'stats'   => $stats,
        ]);
    }
}
