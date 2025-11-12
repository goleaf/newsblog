<?php

namespace App\Http\Controllers\Nova;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class SystemHealthController extends Controller
{
    /**
     * Get system health status.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'databases' => $this->checkDatabases(),
            'queues' => $this->checkQueues(),
            'storage' => $this->checkStorage(),
            'errors' => $this->getRecentErrors(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Check all database connections.
     *
     * @return array<string, array{status: string, error?: string}>
     */
    protected function checkDatabases(): array
    {
        $databases = [];
        $connections = config('database.connections', []);

        foreach ($connections as $name => $config) {
            try {
                DB::connection($name)->getPdo();
                $databases[$name] = [
                    'status' => 'connected',
                ];
            } catch (\Exception $e) {
                $databases[$name] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $databases;
    }

    /**
     * Check all queue connections.
     *
     * @return array<string, array{status: string, size: int, failed_jobs: int, error?: string}>
     */
    protected function checkQueues(): array
    {
        $queues = [];
        $connections = config('queue.connections', []);

        foreach ($connections as $name => $config) {
            try {
                $queue = Queue::connection($name);
                $size = method_exists($queue, 'size') ? $queue->size() : 0;

                $failedJobs = 0;
                if ($config['driver'] === 'database') {
                    $failedJobs = DB::table('failed_jobs')->count();
                }

                $queues[$name] = [
                    'status' => 'active',
                    'size' => $size,
                    'failed_jobs' => $failedJobs,
                    'driver' => $config['driver'] ?? 'unknown',
                ];
            } catch (\Exception $e) {
                $queues[$name] = [
                    'status' => 'failed',
                    'size' => 0,
                    'failed_jobs' => 0,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $queues;
    }

    /**
     * Check all storage disks.
     *
     * @return array<string, array{status: string, total: int|null, free: int|null, used: int|null, usage_percent: float|null, error?: string}>
     */
    protected function checkStorage(): array
    {
        $storage = [];
        $disks = config('filesystems.disks', []);

        foreach ($disks as $name => $config) {
            try {
                $disk = Storage::disk($name);
                $accessible = $disk->exists('.') || $disk->exists('');

                if (! $accessible) {
                    $storage[$name] = [
                        'status' => 'inaccessible',
                        'total' => null,
                        'free' => null,
                        'used' => null,
                        'usage_percent' => null,
                    ];

                    continue;
                }

                $total = null;
                $free = null;
                $used = null;
                $usagePercent = null;

                // Only calculate disk space for local disks
                if ($config['driver'] === 'local' && isset($config['root'])) {
                    $root = $config['root'];
                    if (is_dir($root)) {
                        $total = disk_total_space($root);
                        $free = disk_free_space($root);
                        if ($total !== false && $free !== false) {
                            $used = $total - $free;
                            $usagePercent = $total > 0 ? round(($used / $total) * 100, 2) : 0;
                        }
                    }
                }

                $storage[$name] = [
                    'status' => 'accessible',
                    'total' => $total,
                    'free' => $free,
                    'used' => $used,
                    'usage_percent' => $usagePercent,
                    'driver' => $config['driver'] ?? 'unknown',
                ];
            } catch (\Exception $e) {
                $storage[$name] = [
                    'status' => 'failed',
                    'total' => null,
                    'free' => null,
                    'used' => null,
                    'usage_percent' => null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $storage;
    }

    /**
     * Get recent errors from logs.
     *
     * @return array<int, array{timestamp: string, level: string, message: string}>
     */
    protected function getRecentErrors(): array
    {
        $errors = [];
        $logFile = storage_path('logs/laravel.log');

        if (! File::exists($logFile)) {
            return $errors;
        }

        try {
            $lines = File::lines($logFile)->take(100)->toArray();
            $lines = array_reverse($lines); // Start from most recent

            $errorLevels = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];
            $currentEntry = null;

            foreach ($lines as $line) {
                // Check if line starts a new log entry (Laravel log format)
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] local\.(\w+): (.*)$/', $line, $matches)) {
                    $timestamp = $matches[1];
                    $level = strtoupper($matches[2]);
                    $message = $matches[3];

                    if (in_array($level, $errorLevels)) {
                        $errors[] = [
                            'timestamp' => $timestamp,
                            'level' => $level,
                            'message' => substr($message, 0, 500), // Limit message length
                        ];

                        if (count($errors) >= 20) {
                            break;
                        }
                    }
                } elseif ($currentEntry !== null && ! empty(trim($line))) {
                    // Append continuation lines to the last error
                    $lastIndex = count($errors) - 1;
                    if ($lastIndex >= 0) {
                        $errors[$lastIndex]['message'] .= "\n".trim($line);
                        $errors[$lastIndex]['message'] = substr($errors[$lastIndex]['message'], 0, 500);
                    }
                }
            }
        } catch (\Exception $e) {
            // If log reading fails, return empty array
        }

        return array_reverse($errors); // Return in chronological order (oldest first)
    }
}
