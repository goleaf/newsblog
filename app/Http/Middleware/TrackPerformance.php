<?php

namespace App\Http\Middleware;

use App\Services\PerformanceMetricsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // Enable query log to capture per-request query count
        try {
            DB::connection()->enableQueryLog();
        } catch (\Throwable $e) {
            // ignore if connection does not support query log
        }

        $response = $next($request);

        $loadTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Collect per-request database query count (best-effort)
        $queryCount = 0;
        try {
            $queryLog = DB::getQueryLog();
            $queryCount = is_array($queryLog) ? count($queryLog) : 0;
        } catch (\Throwable $e) {
            $queryCount = 0;
        }

        // Capture peak memory usage for the request
        $peakMemoryBytes = function_exists('memory_get_peak_usage') ? memory_get_peak_usage(true) : 0;

        // Track page load time and per-request stats
        $route = $request->route()?->getName() ?? $request->path();
        $this->performanceMetrics->trackPageLoad($route, $loadTime, $queryCount, $peakMemoryBytes);

        // Alert on very slow pages (>3 seconds)
        $slowRequestThreshold = (int) (config('performance.thresholds.slow_request_ms') ?? 3000);
        if ($loadTime > $slowRequestThreshold) {
            \Illuminate\Support\Facades\Log::channel('performance')->warning('Very slow page load detected', [
                'route' => $route,
                'load_time_ms' => round($loadTime, 2),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'threshold_ms' => $slowRequestThreshold,
            ]);
        }

        // Add performance header for debugging (only in non-production)
        if (! app()->isProduction()) {
            $response->headers->set('X-Page-Load-Time', round($loadTime, 2).'ms');
            $response->headers->set('X-DB-Query-Count', (string) $queryCount);
            $response->headers->set('X-Memory-Peak', (string) $peakMemoryBytes);
        }

        return $response;
    }
}
