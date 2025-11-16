<?php

namespace App\Console\Commands;

use App\Services\MonitoringService;
use App\Services\PerformanceMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPerformanceAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:check-alerts
                            {--threshold-slow-page=2000 : Threshold for slow page load in milliseconds}
                            {--threshold-slow-query=500 : Threshold for slow query in milliseconds}
                            {--threshold-memory=80 : Threshold for memory usage percentage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check performance metrics and alert on issues';

    public function __construct(
        private PerformanceMetricsService $performanceMetrics,
        private MonitoringService $monitoring
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking performance metrics...');

        $alerts = [];

        // Check page load times
        $pageLoadAlerts = $this->checkPageLoadTimes();
        $alerts = array_merge($alerts, $pageLoadAlerts);

        // Check slow queries
        $queryAlerts = $this->checkSlowQueries();
        $alerts = array_merge($alerts, $queryAlerts);

        // Check memory usage
        $memoryAlerts = $this->checkMemoryUsage();
        $alerts = array_merge($alerts, $memoryAlerts);

        // Check monitoring service alerts
        $monitoringAlerts = $this->monitoring->checkAlertThresholds();
        $alerts = array_merge($alerts, $monitoringAlerts);

        if (empty($alerts)) {
            $this->info('✓ All performance metrics are within acceptable ranges.');

            return self::SUCCESS;
        }

        // Display alerts
        $this->warn('⚠ Performance alerts detected:');
        $this->newLine();

        foreach ($alerts as $alert) {
            $severity = $alert['severity'] ?? 'medium';
            $icon = match ($severity) {
                'critical' => '🔴',
                'high' => '🟠',
                'medium' => '🟡',
                default => '🔵',
            };

            $this->line("{$icon} [{$alert['type']}] {$alert['message']}");
        }

        // Log alerts
        foreach ($alerts as $alert) {
            Log::channel('performance')->warning('Performance alert', $alert);
        }

        return self::SUCCESS;
    }

    /**
     * Check page load times for slow pages
     */
    protected function checkPageLoadTimes(): array
    {
        $threshold = (float) $this->option('threshold-slow-page');
        $alerts = [];

        $pageLoads = $this->performanceMetrics->getAveragePageLoadTime();

        foreach ($pageLoads as $load) {
            if ($load['average'] > $threshold) {
                $alerts[] = [
                    'severity' => $load['average'] > $threshold * 2 ? 'critical' : 'high',
                    'type' => 'slow_page',
                    'message' => "Slow page load detected at {$load['hour']}: {$load['average']}ms (threshold: {$threshold}ms)",
                    'value' => $load['average'],
                    'threshold' => $threshold,
                    'hour' => $load['hour'],
                ];
            }
        }

        return $alerts;
    }

    /**
     * Check for slow queries
     */
    protected function checkSlowQueries(): array
    {
        $threshold = (float) $this->option('threshold-slow-query');
        $alerts = [];

        $slowQueries = $this->performanceMetrics->getSlowQueries(1); // Last day only

        $criticalQueries = array_filter($slowQueries, fn ($q) => $q['time'] > $threshold);

        if (count($criticalQueries) > 0) {
            $slowest = max(array_column($criticalQueries, 'time'));
            $alerts[] = [
                'severity' => $slowest > $threshold * 2 ? 'critical' : 'high',
                'type' => 'slow_query',
                'message' => count($criticalQueries).' slow queries detected (slowest: '.number_format($slowest, 2).'ms)',
                'value' => $slowest,
                'threshold' => $threshold,
                'count' => count($criticalQueries),
            ];
        }

        return $alerts;
    }

    /**
     * Check memory usage
     */
    protected function checkMemoryUsage(): array
    {
        $threshold = (float) $this->option('threshold-memory');
        $alerts = [];

        $memory = $this->performanceMetrics->getMemoryUsage();

        if ($memory['percentage'] > $threshold) {
            $alerts[] = [
                'severity' => $memory['percentage'] > 90 ? 'critical' : 'high',
                'type' => 'memory_usage',
                'message' => "High memory usage: {$memory['percentage']}% ({$memory['usage_formatted']} / {$memory['limit_formatted']})",
                'value' => $memory['percentage'],
                'threshold' => $threshold,
            ];
        }

        return $alerts;
    }
}
