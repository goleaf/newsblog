<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Centralized monitoring service for tracking application metrics.
 *
 * Tracks:
 * - DNT compliance metrics
 * - Post view tracking performance
 * - Engagement metrics
 * - Search analytics
 * - Error rates
 */
class MonitoringService
{
    /**
     * Track DNT header presence and compliance.
     */
    public function trackDntCompliance(bool $dntEnabled, string $endpoint): void
    {
        $key = "metrics:dnt:{$endpoint}:".($dntEnabled ? 'enabled' : 'disabled');
        Cache::increment($key);

        // Log DNT events for analysis
        if ($dntEnabled) {
            Log::channel('analytics')->info('DNT header respected', [
                'endpoint' => $endpoint,
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Track post view tracking performance.
     */
    public function trackViewPerformance(int $postId, float $duration, bool $queued = true): void
    {
        $key = 'metrics:post_views:performance';

        // Store performance data
        Cache::put("{$key}:latest", [
            'post_id' => $postId,
            'duration_ms' => round($duration * 1000, 2),
            'queued' => $queued,
            'timestamp' => now()->toIso8601String(),
        ], now()->addHours(24));

        // Increment counters
        Cache::increment('metrics:post_views:total');
        if ($queued) {
            Cache::increment('metrics:post_views:queued');
        }

        // Alert on slow performance
        if ($duration > 1.0) {
            Log::channel('performance')->warning('Slow post view tracking', [
                'post_id' => $postId,
                'duration_ms' => round($duration * 1000, 2),
                'queued' => $queued,
            ]);
        }
    }

    /**
     * Track engagement metric recording.
     */
    public function trackEngagementMetric(string $type, int $postId, ?int $userId = null): void
    {
        $key = "metrics:engagement:{$type}";
        Cache::increment($key);
        Cache::increment('metrics:engagement:total');

        // Track per-post engagement
        Cache::increment("metrics:engagement:post:{$postId}");

        // Track authenticated vs anonymous
        if ($userId) {
            Cache::increment('metrics:engagement:authenticated');
        } else {
            Cache::increment('metrics:engagement:anonymous');
        }
    }

    /**
     * Track search query performance.
     */
    public function trackSearchPerformance(string $query, int $resultCount, float $duration): void
    {
        Cache::increment('metrics:search:total');

        // Track zero-result searches
        if ($resultCount === 0) {
            Cache::increment('metrics:search:zero_results');
            Log::channel('analytics')->info('Zero search results', [
                'query' => $query,
                'duration_ms' => round($duration * 1000, 2),
            ]);
        }

        // Alert on slow searches
        if ($duration > 2.0) {
            Log::channel('performance')->warning('Slow search query', [
                'query' => $query,
                'result_count' => $resultCount,
                'duration_ms' => round($duration * 1000, 2),
            ]);
        }

        // Store latest search performance
        Cache::put('metrics:search:latest', [
            'query' => $query,
            'result_count' => $resultCount,
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => now()->toIso8601String(),
        ], now()->addHours(24));
    }

    /**
     * Track error occurrences.
     */
    public function trackError(string $type, string $message, array $context = []): void
    {
        $key = "metrics:errors:{$type}";
        Cache::increment($key);
        Cache::increment('metrics:errors:total');

        Log::channel('errors')->error($message, array_merge($context, [
            'error_type' => $type,
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Get current metrics snapshot.
     */
    public function getMetricsSnapshot(): array
    {
        return [
            'post_views' => [
                'total' => Cache::get('metrics:post_views:total', 0),
                'queued' => Cache::get('metrics:post_views:queued', 0),
                'latest' => Cache::get('metrics:post_views:performance:latest'),
            ],
            'dnt' => [
                'enabled' => Cache::get('metrics:dnt:post.show:enabled', 0),
                'disabled' => Cache::get('metrics:dnt:post.show:disabled', 0),
            ],
            'engagement' => [
                'total' => Cache::get('metrics:engagement:total', 0),
                'scroll' => Cache::get('metrics:engagement:scroll', 0),
                'time_spent' => Cache::get('metrics:engagement:time_spent', 0),
                'authenticated' => Cache::get('metrics:engagement:authenticated', 0),
                'anonymous' => Cache::get('metrics:engagement:anonymous', 0),
            ],
            'search' => [
                'total' => Cache::get('metrics:search:total', 0),
                'zero_results' => Cache::get('metrics:search:zero_results', 0),
                'latest' => Cache::get('metrics:search:latest'),
            ],
            'errors' => [
                'total' => Cache::get('metrics:errors:total', 0),
                'tracking' => Cache::get('metrics:errors:tracking', 0),
                'database' => Cache::get('metrics:errors:database', 0),
            ],
        ];
    }

    /**
     * Reset metrics (useful for testing or periodic resets).
     */
    public function resetMetrics(): void
    {
        // Clear all metric keys
        $prefixes = [
            'metrics:post_views:',
            'metrics:dnt:',
            'metrics:engagement:',
            'metrics:search:',
            'metrics:errors:',
        ];

        foreach ($prefixes as $prefix) {
            // Clear known keys for each prefix
            Cache::forget($prefix.'total');
            Cache::forget($prefix.'queued');
            Cache::forget($prefix.'latest');
            Cache::forget($prefix.'enabled');
            Cache::forget($prefix.'disabled');
            Cache::forget($prefix.'scroll');
            Cache::forget($prefix.'time_spent');
            Cache::forget($prefix.'authenticated');
            Cache::forget($prefix.'anonymous');
            Cache::forget($prefix.'zero_results');
            Cache::forget($prefix.'tracking');
            Cache::forget($prefix.'database');
            Cache::forget($prefix.'performance:latest');
        }

        // Clear specific endpoint keys
        Cache::forget('metrics:dnt:post.show:enabled');
        Cache::forget('metrics:dnt:post.show:disabled');
        Cache::forget('metrics:dnt:engagement.track:enabled');
        Cache::forget('metrics:dnt:engagement.track:disabled');
    }

    /**
     * Check if any metrics exceed alert thresholds.
     */
    public function checkAlertThresholds(): array
    {
        $alerts = [];
        $metrics = $this->getMetricsSnapshot();

        // Check error rate
        $errorRate = $metrics['errors']['total'];
        if ($errorRate > 100) {
            $alerts[] = [
                'severity' => 'high',
                'type' => 'error_rate',
                'message' => "High error rate detected: {$errorRate} errors",
                'value' => $errorRate,
            ];
        }

        // Check zero-result search rate
        $totalSearches = $metrics['search']['total'];
        $zeroResults = $metrics['search']['zero_results'];
        if ($totalSearches > 0) {
            $zeroResultRate = ($zeroResults / $totalSearches) * 100;
            if ($zeroResultRate > 30) {
                $alerts[] = [
                    'severity' => 'medium',
                    'type' => 'search_quality',
                    'message' => "High zero-result search rate: {$zeroResultRate}%",
                    'value' => $zeroResultRate,
                ];
            }
        }

        return $alerts;
    }
}
