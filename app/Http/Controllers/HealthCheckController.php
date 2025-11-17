<?php

namespace App\Http\Controllers;

use App\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthCheckController extends Controller
{
    public function __construct(
        protected SystemHealthService $healthService
    ) {}

    /**
     * Get overall system health status.
     */
    public function index(): JsonResponse
    {
        $health = $this->healthService->getCachedHealth();

        $statusCode = match ($health['status']) {
            'healthy' => 200,
            'degraded' => 200,
            'unhealthy' => 503,
            default => 500,
        };

        return response()->json($health, $statusCode);
    }

    /**
     * Get health status for a specific component.
     */
    public function component(Request $request, string $component): JsonResponse
    {
        $health = $this->healthService->checkComponent($component);

        $statusCode = match ($health['status']) {
            'healthy' => 200,
            'degraded' => 200,
            'unhealthy' => 503,
            default => 500,
        };

        return response()->json($health, $statusCode);
    }

    /**
     * Simple ping endpoint for uptime monitoring.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
