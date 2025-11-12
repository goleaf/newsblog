<?php

use App\CacheManager\Http\Controllers\CacheController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. They are protected
| by your tool's "Authorize" middleware by default. Now, go build!
|
*/

Route::post('/clear/{type}', [CacheController::class, 'clear']);

Route::post('/clear-all', [CacheController::class, 'clearAll']);

Route::get('/timestamps', [CacheController::class, 'getTimestamps']);
