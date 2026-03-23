<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    /**
     * Map Reduce #1: Total des mises et gains potentiels par sport
     */
    public function betsBySport(): JsonResponse
    {
        $pipeline = [
            ['$match' => ['status' => ['$in' => ['pending', 'won', 'lost']]]],
            ['$group' => [
                '_id'             => '$sport_id',
                'total_bets'      => ['$sum' => 1],
                'total_amount'    => ['$sum' => '$amount'],
                'total_potential' => ['$sum' => '$potential_gain'],
                'won_bets'        => ['$sum' => ['$cond' => [['$eq' => ['$status', 'won']], 1, 0]]],
            ]],
            ['$sort' => ['total_amount' => -1]],
        ];

        $results = Bet::raw(fn($c) => $c->aggregate($pipeline))->toArray();

        return response()->json([
            'operation'   => 'bets_by_sport',
            'description' => 'Total des paris et mises regroupés par sport',
            'data'        => $results,
        ]);
    }

    /**
     * Map Reduce #2: Performance par utilisateur (victoires, pertes, ROI)
     */
    public function userPerformance(): JsonResponse
    {
        $pipeline = [
            ['$match' => ['status' => ['$in' => ['won', 'lost']]]],
            ['$group' => [
                '_id'          => '$user_id',
                'total_bets'   => ['$sum' => 1],
                'total_staked' => ['$sum' => '$amount'],
                'won_bets'     => ['$sum' => ['$cond' => [['$eq' => ['$status', 'won']], 1, 0]]],
                'total_gained' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'won']], '$potential_gain', 0]]],
            ]],
            ['$addFields' => [
                'roi_percent' => [
                    '$multiply' => [
                        ['$divide' => [
                            ['$subtract' => ['$total_gained', '$total_staked']],
                            '$total_staked',
                        ]],
                        100,
                    ],
                ],
            ]],
            ['$sort' => ['total_gained' => -1]],
        ];

        $results = Bet::raw(fn($c) => $c->aggregate($pipeline))->toArray();

        return response()->json([
            'operation'   => 'user_performance',
            'description' => 'Performance ROI par utilisateur sur les paris résolus',
            'data'        => $results,
        ]);
    }
}
