<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

/**
 * Service for monitoring system health across all components.
 */
class SystemHealthService
{
    /**
     * Check overall system health.
     */
    public function checkHealth(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $failedChecks = collect($checks)->filter(fn ($check) => $check['status'] !== 'healthy')->count();

        $overallStatus = match (true) {
            $failedChecks === 0 => 'healthy',
            $failedChecks <= 1 => 'degraded',
            default => 'unhealthy',
        };

        return [
            'status' => $overallStatus,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
            'failed_checks' => $failedChecks,
        ];
    }

    /**
     * Check database connection and health.
     */
    public function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            $pdo = DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            // Get database size
            $dbName = DB::connection()->getDatabaseName();
            $size = DB::selectOne('
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = ?
            ', [$dbName]);

            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'details' => [
                    'response_time_ms' => $responseTime,
                    'size_mb' => $size->size_mb ?? 0,
                    'driver' => DB::connection()->getDriverName(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Check Redis connection and health.
     */
    public function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Cache::store('redis')->put('health_check', true, 10);
            $connected = Cache::store('redis')->get('health_check');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if (! $connected) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Redis connection failed',
                    'details' => [],
                ];
            }

            // Get Redis info
            $info = Redis::connection()->info();

            return [
                'status' => 'healthy',
                'message' => 'Redis connection successful',
                'details' => [
                    'response_time_ms' => $responseTime,
                    'used_memory' => $info['used_memory_human'] ?? 'unknown',
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'uptime_days' => isset($info['uptime_in_seconds']) ? round($info['uptime_in_seconds'] / 86400, 1) : 0,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed',
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Check storage access and health.
     */
    public function checkStorage(): array
    {
        try {
            $start = microtime(true);
            $testFile = 'health_check_'.time().'.txt';
            Storage::put($testFile, 'health check');
            $canRead = Storage::exists($testFile);
            Storage::delete($testFile);
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if (! $canRead) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Storage access failed',
                    'details' => [],
                ];
            }

            // Get storage statistics
            $files = Storage::allFiles('public');
            $totalSize = 0;
            foreach ($files as $file) {
                $totalSize += Storage::size($file);
            }

            return [
                'status' => 'healthy',
                'message' => 'Storage accessible',
                'details' => [
                    'response_time_ms' => $responseTime,
                    'file_count' => count($files),
                    'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                    'driver' => config('filesystems.default'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage access failed',
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Check queue status and health.
     */
    public function checkQueue(): array
    {
        try {
            // Get pending jobs count
            $pendingJobs = DB::table('jobs')->count();

            // Get failed jobs count
            $failedJobs = DB::table('failed_jobs')->count();

            // Determine status based on queue size
            $status = match (true) {
                $pendingJobs > 1000 => 'degraded',
                $failedJobs > 100 => 'degraded',
                default => 'healthy',
            };

            return [
                'status' => $status,
                'message' => 'Queue operational',
                'details' => [
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                    'driver' => config('queue.default'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Queue check failed',
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Get cached health status or check fresh.
     */
    public function getCachedHealth(int $ttl = 60): array
    {
        return Cache::remember('system_health', $ttl, function () {
            return $this->checkHealth();
        });
    }

    /**
     * Check if system is healthy.
     */
    public function isHealthy(): bool
    {
        $health = $this->getCachedHealth();

        return $health['status'] === 'healthy';
    }

    /**
     * Get health status for a specific component.
     */
    public function checkComponent(string $component): array
    {
        return match ($component) {
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            default => [
                'status' => 'unknown',
                'message' => 'Unknown component',
                'details' => [],
            ],
        };
    }
}
