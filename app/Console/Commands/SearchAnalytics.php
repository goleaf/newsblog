<?php

namespace App\Console\Commands;

use App\Services\SearchAnalyticsService;
use Illuminate\Console\Command;

class SearchAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:analytics 
                            {--period=day : Time period (day, week, month, year)}
                            {--top=20 : Number of top queries to display}
                            {--no-results=50 : Number of no-result queries to display}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display search analytics including top queries, no-result queries, and performance metrics';

    /**
     * Execute the console command.
     */
    public function handle(SearchAnalyticsService $searchAnalyticsService): int
    {
        $period = $this->option('period');
        $topLimit = (int) $this->option('top');
        $noResultsLimit = (int) $this->option('no-results');

        $validPeriods = ['day', 'week', 'month', 'year'];

        if (! in_array($period, $validPeriods, true)) {
            $this->error("Invalid period: {$period}. Valid periods are: ".implode(', ', $validPeriods));

            return self::FAILURE;
        }

        $this->info("Search Analytics Report ({$period})");
        $this->newLine();

        // Performance Metrics
        $this->info('Performance Metrics:');
        $this->line('─────────────────────────────────────────────────────────');

        try {
            $metrics = $searchAnalyticsService->getPerformanceMetrics($period);

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Searches', number_format($metrics['total_searches'])],
                    ['Average Execution Time', $metrics['avg_execution_time'].' ms'],
                    ['Min Execution Time', $metrics['min_execution_time'].' ms'],
                    ['Max Execution Time', $metrics['max_execution_time'].' ms'],
                    ['Average Result Count', number_format($metrics['avg_result_count'], 2)],
                    ['No Result Searches', number_format($metrics['no_result_searches'])],
                    ['No Result Percentage', $metrics['no_result_percentage'].'%'],
                ]
            );

            $this->newLine();

            // Top Queries
            $this->info("Top {$topLimit} Search Queries:");
            $this->line('─────────────────────────────────────────────────────────');

            $topQueries = $searchAnalyticsService->getTopQueries($topLimit, $period);

            if ($topQueries->isEmpty()) {
                $this->line('No search queries found for this period.');
            } else {
                $tableData = $topQueries->map(function ($query) {
                    return [
                        $query->query,
                        number_format($query->count),
                    ];
                })->toArray();

                $this->table(
                    ['Query', 'Count'],
                    $tableData
                );
            }

            $this->newLine();

            // No Result Queries
            $this->info("Top {$noResultsLimit} Queries with No Results:");
            $this->line('─────────────────────────────────────────────────────────');

            $noResultQueries = $searchAnalyticsService->getNoResultQueries($noResultsLimit);

            if ($noResultQueries->isEmpty()) {
                $this->line('No queries with zero results found.');
            } else {
                $tableData = $noResultQueries->map(function ($query) {
                    return [
                        $query->query,
                        number_format($query->count),
                    ];
                })->toArray();

                $this->table(
                    ['Query', 'Count'],
                    $tableData
                );
            }

            $this->newLine();

            // Click-Through Rate
            $ctr = $searchAnalyticsService->getClickThroughRate($period);
            $this->info("Click-Through Rate: {$ctr}%");

            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to retrieve search analytics: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
