<?php

use App\MaintenanceMode\Http\Controllers\MaintenanceController;
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

Route::get('/status', [MaintenanceController::class, 'getStatus']);

Route::post('/toggle', [MaintenanceController::class, 'toggle']);

Route::post('/message', [MaintenanceController::class, 'updateMessage']);

Route::post('/ip-whitelist', [MaintenanceController::class, 'updateIpWhitelist']);
