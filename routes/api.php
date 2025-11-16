<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostInteractionController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Nova\SystemHealthController;
use Illuminate\Support\Facades\Route;

// Nova System Health API endpoint
Route::get('/nova-api/system-health', [SystemHealthController::class, 'index'])
    ->name('nova.system-health');

Route::prefix('v1')->middleware(['throttle:api'])->group(function () {
    // Public endpoints
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{slug}', [PostController::class, 'show']);
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/categories/{id}/articles', [\App\Http\Controllers\Api\CategoryController::class, 'articles']);
    Route::get('/tags', [\App\Http\Controllers\Api\TagController::class, 'index']);
    Route::get('/tags/{id}/articles', [\App\Http\Controllers\Api\TagController::class, 'articles']);
    Route::get('/comments', [\App\Http\Controllers\Api\CommentController::class, 'index']);

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
        Route::get('/users/me', [\App\Http\Controllers\Api\UserController::class, 'me']);
        Route::get('/bookmarks', [\App\Http\Controllers\Api\BookmarkController::class, 'index']);
        // Post CRUD
        Route::post('/articles', [PostController::class, 'store']);
        Route::put('/articles/{post}', [PostController::class, 'update']);
        Route::delete('/articles/{post}', [PostController::class, 'destroy']);

        // Interactions
        Route::post('/posts/{postId}/reactions', [PostInteractionController::class, 'react']);
        Route::post('/posts/{postId}/bookmark', [PostInteractionController::class, 'bookmark']);
        Route::post('/comments/{commentId}/reactions', [\App\Http\Controllers\Api\CommentReactionController::class, 'react']);
        Route::post('/comments/{commentId}/flags', [\App\Http\Controllers\Api\CommentFlagController::class, 'store']);
        Route::post('/comments', [\App\Http\Controllers\Api\CommentController::class, 'store']);
        Route::put('/comments/{comment}', [\App\Http\Controllers\Api\CommentController::class, 'update']);
        Route::delete('/comments/{comment}', [\App\Http\Controllers\Api\CommentController::class, 'destroy']);

        // Sanctum token management
        Route::get('/tokens', [\App\Http\Controllers\Api\TokenController::class, 'index']);
        Route::post('/tokens', [\App\Http\Controllers\Api\TokenController::class, 'store']);
        Route::delete('/tokens/{tokenId}', [\App\Http\Controllers\Api\TokenController::class, 'destroy']);
    });

    // Moderation endpoints (admin/editor only)
    Route::middleware(['auth:sanctum', 'role:admin,editor'])->group(function () {
        Route::get('/moderation/flags', [\App\Http\Controllers\Api\ModerationController::class, 'flagsIndex']);
        Route::post('/moderation/flags/{flag}/review', [\App\Http\Controllers\Api\ModerationController::class, 'review']);
        Route::post('/moderation/flags/bulk-review', [\App\Http\Controllers\Api\ModerationController::class, 'bulkReview']);
    });

    // Admin endpoints
    // Note: Admin operations are now handled through Nova's API
    // This group is kept for backward compatibility but should use Nova's authentication
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Admin API routes have been migrated to Nova
        // Use Nova's API endpoints for admin operations
    });
});
