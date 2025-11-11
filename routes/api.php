<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostInteractionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:60,1'])->group(function () {
    // Public endpoints
    Route::get('/posts', [PostController::class, 'index'])->middleware('throttle:100,1');
    Route::get('/posts/{slug}', [PostController::class, 'show'])->middleware('throttle:100,1');

    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/posts/{postId}/reactions', [PostInteractionController::class, 'react']);
        Route::post('/posts/{postId}/bookmark', [PostInteractionController::class, 'bookmark']);
    });

    // Admin endpoints
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Add admin routes here
    });
});
