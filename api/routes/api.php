<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SportController;
use App\Http\Controllers\Api\V1\SportMatchController;
use App\Http\Controllers\Api\V1\TeamController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/public/ping', function () {
        return response()->json(['message' => 'pong']);
    });

    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::apiResource('sports', SportController::class)->only(['index', 'show']);
    Route::apiResource('teams', TeamController::class)->only(['index', 'show']);
    Route::apiResource('matches', SportMatchController::class)
        ->parameters(['matches' => 'matchItem'])
        ->only(['index', 'show']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
        Route::get('/admin/ping', function () {
            return response()->json(['message' => 'admin pong']);
        });

        Route::apiResource('sports', SportController::class)->except(['index', 'show']);
        Route::apiResource('teams', TeamController::class)->except(['index', 'show']);
        Route::apiResource('matches', SportMatchController::class)
            ->parameters(['matches' => 'matchItem'])
            ->except(['index', 'show']);
    });
});
