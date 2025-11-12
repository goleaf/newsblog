<?php

namespace App\Http\Middleware;

use App\Services\PerformanceMetricsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPerformance
{
    public function __construct(private PerformanceMetricsService $performanceMetrics) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $loadTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Track page load time
        $route = $request->route()?->getName() ?? $request->path();
        $this->performanceMetrics->trackPageLoad($route, $loadTime);

        return $response;
    }
}
