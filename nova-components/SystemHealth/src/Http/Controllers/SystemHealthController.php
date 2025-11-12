<?php

namespace App\SystemHealth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class SystemHealthController
{
    /**
     * Get system health status.
     */
    public function getStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        try {
            $data = [
                'database' => $this->getDatabaseStatus(),
                'queue' => $this->getQueueStatus(),
                'storage' => $this->getStorageStatus(),
                'errors' => $this->getRecentErrors(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve system health: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get database connection status.
     */
    protected function getDatabaseStatus(): array
    {
        try {
            DB::connection()->getPdo();
            $connected = true;
            $message = 'Connected';
        } catch (\Exception $e) {
            $connected = false;
            $message = 'Connection failed: '.$e->getMessage();
        }

        return [
            'connected' => $connected,
            'message' => $message,
            'driver' => config('database.default'),
        ];
    }

    /**
     * Get queue status.
     */
    protected function getQueueStatus(): array
    {
        try {
            // Get failed jobs count
            $failedJobsCount = DB::table('failed_jobs')->count();

            // Get pending jobs count (if using database queue)
            $pendingJobsCount = 0;
            if (config('queue.default') === 'database') {
                $pendingJobsCount = DB::table('jobs')->count();
            }

            return [
                'driver' => config('queue.default'),
                'failed_jobs' => $failedJobsCount,
                'pending_jobs' => $pendingJobsCount,
                'status' => $failedJobsCount > 0 ? 'warning' : 'healthy',
            ];
        } catch (\Exception $e) {
            return [
                'driver' => config('queue.default'),
                'failed_jobs' => 0,
                'pending_jobs' => 0,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get storage usage.
     */
    protected function getStorageStatus(): array
    {
        try {
            $storagePath = storage_path();
            $totalSpace = disk_total_space($storagePath);
            $freeSpace = disk_free_space($storagePath);
            $usedSpace = $totalSpace - $freeSpace;
            $usedPercentage = ($usedSpace / $totalSpace) * 100;

            return [
                'total' => $this->formatBytes($totalSpace),
                'used' => $this->formatBytes($usedSpace),
                'free' => $this->formatBytes($freeSpace),
                'used_percentage' => round($usedPercentage, 2),
                'status' => $usedPercentage > 90 ? 'critical' : ($usedPercentage > 75 ? 'warning' : 'healthy'),
            ];
        } catch (\Exception $e) {
            return [
                'total' => 'N/A',
                'used' => 'N/A',
                'free' => 'N/A',
                'used_percentage' => 0,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get recent errors from log files.
     */
    protected function getRecentErrors(): array
    {
        try {
            $logFile = storage_path('logs/laravel.log');

            if (! File::exists($logFile)) {
                return [
                    'count' => 0,
                    'errors' => [],
                ];
            }

            $logContent = File::get($logFile);
            $lines = explode("\n", $logContent);

            // Get last 100 lines
            $recentLines = array_slice($lines, -100);

            // Filter for error and critical entries
            $errors = [];
            foreach ($recentLines as $line) {
                if (preg_match('/\[(.*?)\] (local|production)\.(ERROR|CRITICAL): (.*)/', $line, $matches)) {
                    $errors[] = [
                        'timestamp' => $matches[1] ?? 'Unknown',
                        'level' => $matches[3] ?? 'ERROR',
                        'message' => substr($matches[4] ?? '', 0, 200), // Limit message length
                    ];
                }
            }

            // Get only the last 10 errors
            $errors = array_slice($errors, -10);

            return [
                'count' => count($errors),
                'errors' => array_reverse($errors), // Most recent first
            ];
        } catch (\Exception $e) {
            return [
                'count' => 0,
                'errors' => [],
                'message' => 'Failed to read log file: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Format bytes to human-readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
