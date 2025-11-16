<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PerformanceMetricsService
{
    private const DEFAULT_SLOW_QUERY_THRESHOLD = 100; // milliseconds

    private const DEFAULT_MEMORY_ALERT_THRESHOLD = 80; // percent

    /**
     * Track page load time and optional per-request stats.
     */
    public function trackPageLoad(string $route, float $loadTime, ?int $queryCount = null, ?int $memoryPeakBytes = null): void
    {
        $key = 'performance.page_loads.'.date('Y-m-d-H');

        $data = Cache::get($key, []);
        $data[] = [
            'route' => $route,
            'load_time' => $loadTime,
            'query_count' => $queryCount,
            'memory_peak' => $memoryPeakBytes,
            'timestamp' => now()->toIso8601String(),
        ];

        Cache::put($key, $data, now()->addHours(25));
    }

    /**
     * Get average page load time for the last 24 hours
     */
    public function getAveragePageLoadTime(): array
    {
        $hours = [];
        $now = now();

        for ($i = 0; $i < 24; $i++) {
            $hour = $now->copy()->subHours($i);
            $key = 'performance.page_loads.'.$hour->format('Y-m-d-H');
            $data = Cache::get($key, []);

            if (! empty($data)) {
                $loadTimes = array_column($data, 'load_time');
                $hours[] = [
                    'hour' => $hour->format('Y-m-d H:00'),
                    'average' => round(array_sum($loadTimes) / count($loadTimes), 2),
                    'count' => count($loadTimes),
                ];
            }
        }

        return array_reverse($hours);
    }

    /**
     * Get average memory peak (in MB) per hour for the last 24 hours.
     * Uses the memory_peak values captured by the middleware.
     */
    public function getAverageMemoryUsage(): array
    {
        $hours = [];
        $now = now();

        for ($i = 0; $i < 24; $i++) {
            $hour = $now->copy()->subHours($i);
            $key = 'performance.page_loads.'.$hour->format('Y-m-d-H');
            $data = Cache::get($key, []);

            if (! empty($data)) {
                $peaks = array_values(array_filter(array_map(
                    fn ($row) => $row['memory_peak'] ?? null,
                    $data
                ), fn ($v) => $v !== null));

                if (! empty($peaks)) {
                    $avgBytes = array_sum($peaks) / count($peaks);
                    $avgMb = $avgBytes / (1024 * 1024);
                    $hours[] = [
                        'hour' => $hour->format('Y-m-d H:00'),
                        'average_mb' => round($avgMb, 2),
                        'count' => count($peaks),
                    ];
                }
            }
        }

        return array_reverse($hours);
    }

    /**
     * Log slow query
     */
    public function logSlowQuery(string $sql, float $time, array $bindings = []): void
    {
        $threshold = (int) (config('performance.thresholds.slow_query_ms') ?? self::DEFAULT_SLOW_QUERY_THRESHOLD);
        if ($time >= $threshold) {
            Log::channel('daily')->warning('Slow query detected', [
                'sql' => $sql,
                'time' => $time,
                'bindings' => $bindings,
                'threshold' => $threshold,
            ]);

            // Store in cache for dashboard
            $key = 'performance.slow_queries.'.date('Y-m-d');
            $queries = Cache::get($key, []);
            $queries[] = [
                'sql' => $sql,
                'time' => $time,
                'bindings' => $bindings,
                'timestamp' => now()->toIso8601String(),
            ];

            Cache::put($key, $queries, now()->addDays(8));
        }
    }

    /**
     * Get slow queries for the last 7 days
     */
    public function getSlowQueries(int $days = 7): array
    {
        $queries = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);
            $key = 'performance.slow_queries.'.$date->format('Y-m-d');
            $dayQueries = Cache::get($key, []);

            foreach ($dayQueries as $query) {
                $queries[] = $query;
            }
        }

        // Sort by time descending
        usort($queries, fn ($a, $b) => $b['time'] <=> $a['time']);

        return array_slice($queries, 0, 50); // Return top 50
    }

    /**
     * Track cache hit/miss
     */
    public function trackCacheHit(bool $hit): void
    {
        $key = 'performance.cache_stats.'.date('Y-m-d');
        $stats = Cache::get($key, ['hits' => 0, 'misses' => 0]);

        if ($hit) {
            $stats['hits']++;
        } else {
            $stats['misses']++;
        }

        Cache::put($key, $stats, now()->addDays(8));
    }

    /**
     * Get cache hit/miss ratio for the last 7 days
     */
    public function getCacheStats(int $days = 7): array
    {
        $stats = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);
            $key = 'performance.cache_stats.'.$date->format('Y-m-d');
            $dayStats = Cache::get($key, ['hits' => 0, 'misses' => 0]);

            $total = $dayStats['hits'] + $dayStats['misses'];
            $ratio = $total > 0 ? round(($dayStats['hits'] / $total) * 100, 2) : 0;

            $stats[] = [
                'date' => $date->format('Y-m-d'),
                'hits' => $dayStats['hits'],
                'misses' => $dayStats['misses'],
                'ratio' => $ratio,
            ];
        }

        return array_reverse($stats);
    }

    /**
     * Get current memory usage
     */
    public function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        $percentage = $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 2) : 0;

        $thresholdPercent = (int) (config('performance.thresholds.memory_alert_percent') ?? self::DEFAULT_MEMORY_ALERT_THRESHOLD);
        $alert = $percentage >= $thresholdPercent;

        if ($alert) {
            Log::channel('daily')->warning('High memory usage detected', [
                'usage' => $this->formatBytes($memoryUsage),
                'limit' => $this->formatBytes($memoryLimit),
                'percentage' => $percentage,
            ]);
        }

        return [
            'usage' => $memoryUsage,
            'usage_formatted' => $this->formatBytes($memoryUsage),
            'limit' => $memoryLimit,
            'limit_formatted' => $this->formatBytes($memoryLimit),
            'percentage' => $percentage,
            'alert' => $alert,
        ];
    }

    /**
     * Get memory limit in bytes
     */
    private function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return 0; // Unlimited
        }

        $unit = strtoupper(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        return match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => (int) $memoryLimit,
        };
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $power), 2).' '.$units[$power];
    }

    /**
     * Get all performance metrics
     */
    public function getAllMetrics(): array
    {
        return [
            'page_loads' => $this->getAveragePageLoadTime(),
            'slow_queries' => $this->getSlowQueries(),
            'cache_stats' => $this->getCacheStats(),
            'memory' => $this->getMemoryUsage(),
        ];
    }

    /**
     * Get average query count per hour for the last 24 hours.
     */
    public function getAverageQueryCount(): array
    {
        $hours = [];
        $now = now();

        for ($i = 0; $i < 24; $i++) {
            $hour = $now->copy()->subHours($i);
            $key = 'performance.page_loads.'.$hour->format('Y-m-d-H');
            $data = Cache::get($key, []);

            if (! empty($data)) {
                $counts = array_values(array_filter(array_map(
                    fn ($row) => $row['query_count'] ?? null,
                    $data
                ), fn ($v) => $v !== null));

                if (! empty($counts)) {
                    $avg = array_sum($counts) / count($counts);
                    $hours[] = [
                        'hour' => $hour->format('Y-m-d H:00'),
                        'average' => round($avg, 2),
                        'count' => count($counts),
                    ];
                }
            }
        }

        return array_reverse($hours);
    }
}
