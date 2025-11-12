<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorNovaPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nova:monitor-performance 
                            {--json : Output results as JSON}
                            {--period=hour : Time period (hour, day)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Nova performance metrics including response times, errors, and database queries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $period = $this->option('period');
        $outputJson = $this->option('json');

        $validPeriods = ['hour', 'day'];

        if (! in_array($period, $validPeriods, true)) {
            $this->error("Invalid period: {$period}. Valid periods are: ".implode(', ', $validPeriods));

            return self::FAILURE;
        }

        $metrics = $this->collectMetrics($period);

        if ($outputJson) {
            $this->line(json_encode($metrics, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->displayMetrics($metrics);

        return self::SUCCESS;
    }

    /**
     * Collect performance metrics.
     *
     * @return array<string, mixed>
     */
    protected function collectMetrics(string $period): array
    {
        $since = $period === 'hour' ? now()->subHour() : now()->subDay();

        // Count errors from logs
        $errorCount = $this->countErrors($since);

        // Count slow queries (if query logging is enabled)
        $slowQueryCount = $this->countSlowQueries($since);

        // Get resource access counts
        $resourceAccess = $this->getResourceAccessCounts($since);

        // Get queue status
        $queueStatus = $this->getQueueStatus();

        // Get memory usage
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
        $memoryPeak = memory_get_peak_usage(true) / 1024 / 1024; // MB

        return [
            'timestamp' => now()->toIso8601String(),
            'period' => $period,
            'errors' => [
                'total' => $errorCount['total'],
                'authentication' => $errorCount['authentication'],
                'authorization' => $errorCount['authorization'],
                'server' => $errorCount['server'],
                'database' => $errorCount['database'],
            ],
            'performance' => [
                'slow_queries' => $slowQueryCount,
                'memory_usage_mb' => round($memoryUsage, 2),
                'memory_peak_mb' => round($memoryPeak, 2),
            ],
            'resource_access' => $resourceAccess,
            'queue' => $queueStatus,
        ];
    }

    /**
     * Count errors from logs.
     *
     * @return array<string, int>
     */
    protected function countErrors(\DateTimeInterface $since): array
    {
        $logFile = storage_path('logs/laravel.log');
        $novaLogFile = storage_path('logs/nova.log');

        $errors = [
            'total' => 0,
            'authentication' => 0,
            'authorization' => 0,
            'server' => 0,
            'database' => 0,
        ];

        if (! file_exists($logFile)) {
            return $errors;
        }

        $sinceTimestamp = $since->format('Y-m-d H');

        // Read log file (last 1000 lines to avoid memory issues)
        $lines = file($logFile);
        $recentLines = array_slice($lines, -1000);

        foreach ($recentLines as $line) {
            if (stripos($line, 'nova') === false) {
                continue;
            }

            if (strpos($line, $sinceTimestamp) === false) {
                continue;
            }

            $errors['total']++;

            if (stripos($line, 'authentication') !== false || stripos($line, 'unauthorized') !== false || stripos($line, '401') !== false) {
                $errors['authentication']++;
            }

            if (stripos($line, 'authorization') !== false || stripos($line, 'forbidden') !== false || stripos($line, '403') !== false || stripos($line, 'permission') !== false) {
                $errors['authorization']++;
            }

            if (stripos($line, '500') !== false || stripos($line, 'Internal Server Error') !== false || stripos($line, 'exception') !== false) {
                $errors['server']++;
            }

            if (stripos($line, 'database') !== false || stripos($line, 'sql') !== false || stripos($line, 'query') !== false) {
                $errors['database']++;
            }
        }

        // Check Nova-specific log file
        if (file_exists($novaLogFile)) {
            $novaLines = file($novaLogFile);
            $recentNovaLines = array_slice($novaLines, -500);

            foreach ($recentNovaLines as $line) {
                if (strpos($line, $sinceTimestamp) === false) {
                    continue;
                }

                if (stripos($line, 'error') !== false || stripos($line, 'exception') !== false) {
                    $errors['total']++;
                }
            }
        }

        return $errors;
    }

    /**
     * Count slow queries.
     */
    protected function countSlowQueries(\DateTimeInterface $since): int
    {
        $queryLogFile = storage_path('logs/query.log');

        if (! file_exists($queryLogFile)) {
            return 0;
        }

        $sinceTimestamp = $since->format('Y-m-d H');
        $slowQueryCount = 0;

        $lines = file($queryLogFile);
        $recentLines = array_slice($lines, -500);

        foreach ($recentLines as $line) {
            if (stripos($line, 'nova') === false) {
                continue;
            }

            if (strpos($line, $sinceTimestamp) === false) {
                continue;
            }

            // Look for query time > 100ms
            if (preg_match('/time[:\s]+(\d+)/i', $line, $matches)) {
                $queryTime = (int) $matches[1];
                if ($queryTime > 100) {
                    $slowQueryCount++;
                }
            }
        }

        return $slowQueryCount;
    }

    /**
     * Get resource access counts.
     *
     * @return array<string, int>
     */
    protected function getResourceAccessCounts(\DateTimeInterface $since): array
    {
        // This is a placeholder - in a real implementation, you might track
        // Nova API requests in a separate log or database table
        return [
            'posts' => Post::where('created_at', '>=', $since)->count(),
            'users' => User::where('created_at', '>=', $since)->count(),
        ];
    }

    /**
     * Get queue status.
     *
     * @return array<string, mixed>
     */
    protected function getQueueStatus(): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            $pendingJobs = DB::table('jobs')->count();

            return [
                'failed' => $failedJobs,
                'pending' => $pendingJobs,
            ];
        } catch (\Exception $e) {
            return [
                'failed' => 0,
                'pending' => 0,
                'error' => 'Queue tables not available',
            ];
        }
    }

    /**
     * Display metrics in a formatted table.
     *
     * @param  array<string, mixed>  $metrics
     */
    protected function displayMetrics(array $metrics): void
    {
        $this->info('Nova Performance Metrics');
        $this->newLine();

        $this->info('Errors:');
        $this->table(
            ['Type', 'Count'],
            [
                ['Total', $metrics['errors']['total']],
                ['Authentication', $metrics['errors']['authentication']],
                ['Authorization', $metrics['errors']['authorization']],
                ['Server (500)', $metrics['errors']['server']],
                ['Database', $metrics['errors']['database']],
            ]
        );

        $this->newLine();
        $this->info('Performance:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Slow Queries (>100ms)', $metrics['performance']['slow_queries']],
                ['Memory Usage', round($metrics['performance']['memory_usage_mb'], 2).' MB'],
                ['Memory Peak', round($metrics['performance']['memory_peak_mb'], 2).' MB'],
            ]
        );

        $this->newLine();
        $this->info('Queue Status:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Failed Jobs', $metrics['queue']['failed']],
                ['Pending Jobs', $metrics['queue']['pending']],
            ]
        );
    }
}
