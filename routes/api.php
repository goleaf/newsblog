<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostInteractionController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Nova\SystemHealthController;
use Illuminate\Support\Facades\Route;

// Nova System Health API endpoint
Route::get('/nova-api/system-health', [SystemHealthController::class, 'index'])
    ->name('nova.system-health');

Route::prefix('v1')->middleware(['throttle:60,1'])->group(function () {
    // Public endpoints
    Route::get('/posts', [PostController::class, 'index'])->middleware('throttle:100,1');
    Route::get('/posts/{slug}', [PostController::class, 'show'])->middleware('throttle:100,1');

    // Search endpoints with custom rate limiting
    Route::prefix('search')->middleware('throttle:search')->group(function () {
        Route::get('/', [SearchController::class, 'search'])->name('api.search');
        Route::get('/suggestions', [SearchController::class, 'suggestions'])->name('api.search.suggestions');
    });

    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/posts/{postId}/reactions', [PostInteractionController::class, 'react']);
        Route::post('/posts/{postId}/bookmark', [PostInteractionController::class, 'bookmark']);
    });

    // Admin endpoints
    // Note: Admin operations are now handled through Nova's API
    // This group is kept for backward compatibility but should use Nova's authentication
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Admin API routes have been migrated to Nova
        // Use Nova's API endpoints for admin operations
    });
});
