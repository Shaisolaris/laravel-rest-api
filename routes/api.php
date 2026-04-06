<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UsersController;
use App\Http\Controllers\Api\V1\ProjectsController;
use App\Http\Controllers\Api\V1\ApiKeysController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
| Base URL: /api/v1
|
| Auth: JWT Bearer token on all authenticated routes.
|       Rate limiting: 60 req/min global, 10 req/15min on auth endpoints.
*/

Route::prefix('v1')->group(function () {

    // Health check (unauthenticated)
    Route::get('/health', fn () => response()->json([
        'status'    => 'ok',
        'version'   => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]));

    // Auth endpoints — stricter rate limit
    Route::prefix('auth')->middleware('throttle:10,15')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login',    [AuthController::class, 'login']);
        Route::post('/refresh',  [AuthController::class, 'refresh']);
    });

    // API key verification (public but rate-limited)
    Route::post('/api-keys/verify', [ApiKeysController::class, 'verify'])
        ->middleware('throttle:30,1');

    // Authenticated routes
    Route::middleware(['auth:api', 'tenant.active'])->group(function () {

        // Current user
        Route::get('/auth/me',     [AuthController::class, 'me']);
        Route::post('/auth/logout',[AuthController::class, 'logout']);

        // Users — admin/owner only
        Route::middleware('role:owner,admin')->group(function () {
            Route::get('/users',              [UsersController::class, 'index']);
            Route::get('/users/{id}',         [UsersController::class, 'show']);
            Route::post('/users',             [UsersController::class, 'store']);
            Route::patch('/users/{id}/role',  [UsersController::class, 'updateRole']);
            Route::delete('/users/{id}',      [UsersController::class, 'deactivate']);
        });

        // Projects — all authenticated users
        Route::get('/projects',       [ProjectsController::class, 'index']);
        Route::get('/projects/{id}',  [ProjectsController::class, 'show']);
        Route::post('/projects',      [ProjectsController::class, 'store']);
        Route::patch('/projects/{id}',[ProjectsController::class, 'update'])->middleware('role:owner,admin,member');
        Route::delete('/projects/{id}',[ProjectsController::class, 'destroy'])->middleware('role:owner,admin');

        // API Keys — admin/owner only
        Route::middleware('role:owner,admin')->group(function () {
            Route::get('/api-keys',        [ApiKeysController::class, 'index']);
            Route::post('/api-keys',       [ApiKeysController::class, 'store']);
            Route::delete('/api-keys/{id}',[ApiKeysController::class, 'revoke']);
        });
    });
});
// Demo mode: seed data available via php artisan db:seed
