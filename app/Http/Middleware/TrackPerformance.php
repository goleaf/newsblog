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

        // Alert on very slow pages (>3 seconds)
        if ($loadTime > 3000) {
            \Illuminate\Support\Facades\Log::channel('performance')->warning('Very slow page load detected', [
                'route' => $route,
                'load_time_ms' => round($loadTime, 2),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);
        }

        // Add performance header for debugging (only in non-production)
        if (! app()->isProduction()) {
            $response->headers->set('X-Page-Load-Time', round($loadTime, 2).'ms');
        }

        return $response;
    }
}
