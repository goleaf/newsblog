<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostInteractionController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Nova\SystemHealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\Api\StockController;

// Nova System Health API endpoint
Route::get('/nova-api/system-health', [SystemHealthController::class, 'index'])
    ->name('nova.system-health');

Route::prefix('v1')->middleware(['throttle:api'])->group(function () {
    // Public endpoints
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{slug}', [PostController::class, 'show']);

    // Media Library (public, no auth)
    Route::get('/media', [MediaController::class, 'index'])->name('api.media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('api.media.store');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('api.media.destroy');

    // Search endpoints with custom rate limiting
    Route::prefix('search')->middleware('throttle:search')->group(function () {
        Route::get('/', [SearchController::class, 'search'])->name('api.search');
        Route::get('/suggestions', [SearchController::class, 'suggestions'])->name('api.search.suggestions');
    });

    // Widgets data endpoints
    Route::prefix('widgets')->group(function () {
        // Weather: accepts optional lat, lon, and fallback_city
        Route::get('/weather', [WeatherController::class, 'current'])->name('api.widgets.weather');
        // Stocks: accepts comma-separated symbols param
        Route::get('/stocks', [StockController::class, 'tickers'])->name('api.widgets.stocks');
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
