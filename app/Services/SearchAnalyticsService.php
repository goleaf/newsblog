<?php

namespace App\Services;

use App\Models\SearchClick;
use App\Models\SearchLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchAnalyticsService
{
    /**
     * Log a search query
     */
    public function logQuery(
        string $query,
        int $resultCount,
        float $executionTime,
        array $metadata = []
    ): void {
        try {
            SearchLog::create([
                'query' => $query,
                'result_count' => $resultCount,
                'execution_time' => $executionTime,
                'search_type' => $metadata['search_type'] ?? 'posts',
                'fuzzy_enabled' => $metadata['fuzzy_enabled'] ?? true,
                'threshold' => $metadata['threshold'] ?? null,
                'filters' => $metadata['filters'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log search query', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log a search result click
     */
    public function logClick(int $searchLogId, int $postId, int $position): void
    {
        try {
            SearchClick::create([
                'search_log_id' => $searchLogId,
                'post_id' => $postId,
                'position' => $position,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log search click', [
                'search_log_id' => $searchLogId,
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get top search queries
     */
    public function getTopQueries(int $limit = 20, string $period = 'month'): Collection
    {
        $query = SearchLog::select('query', DB::raw('COUNT(*) as count'))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit);

        return $this->applyPeriodFilter($query, $period)->get();
    }

    /**
     * Get queries with no results
     */
    public function getNoResultQueries(int $limit = 50): Collection
    {
        return SearchLog::noResults()
            ->select('query', DB::raw('COUNT(*) as count'))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get average search performance metrics
     */
    public function getPerformanceMetrics(string $period = 'day'): array
    {
        $query = SearchLog::query();
        $query = $this->applyPeriodFilter($query, $period);

        $metrics = $query->selectRaw('
            AVG(execution_time) as avg_execution_time,
            MAX(execution_time) as max_execution_time,
            MIN(execution_time) as min_execution_time,
            COUNT(*) as total_searches,
            SUM(CASE WHEN result_count = 0 THEN 1 ELSE 0 END) as no_result_searches,
            AVG(result_count) as avg_result_count
        ')->first();

        return [
            'avg_execution_time' => round($metrics->avg_execution_time ?? 0, 2),
            'max_execution_time' => round($metrics->max_execution_time ?? 0, 2),
            'min_execution_time' => round($metrics->min_execution_time ?? 0, 2),
            'total_searches' => $metrics->total_searches ?? 0,
            'no_result_searches' => $metrics->no_result_searches ?? 0,
            'avg_result_count' => round($metrics->avg_result_count ?? 0, 2),
            'no_result_percentage' => $metrics->total_searches > 0
                ? round(($metrics->no_result_searches / $metrics->total_searches) * 100, 2)
                : 0,
        ];
    }

    /**
     * Archive old search logs
     */
    public function archiveLogs(int $daysToKeep = 90): int
    {
        try {
            $cutoffDate = now()->subDays($daysToKeep);

            $count = SearchLog::where('created_at', '<', $cutoffDate)->count();

            SearchLog::where('created_at', '<', $cutoffDate)->delete();

            Log::info('Search logs archived', [
                'archived_count' => $count,
                'cutoff_date' => $cutoffDate->toDateString(),
            ]);

            return $count;
        } catch (\Exception $e) {
            Log::error('Failed to archive search logs', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Get click-through rate for searches
     */
    public function getClickThroughRate(string $period = 'day'): float
    {
        $query = SearchLog::query();
        $query = $this->applyPeriodFilter($query, $period);

        $totalSearches = $query->count();

        if ($totalSearches === 0) {
            return 0.0;
        }

        $searchesWithClicks = SearchLog::query()
            ->whereHas('clicks')
            ->when($period, function ($q) use ($period) {
                return $this->applyPeriodFilter($q, $period);
            })
            ->count();

        return round(($searchesWithClicks / $totalSearches) * 100, 2);
    }

    /**
     * Get most clicked posts from search results
     */
    public function getMostClickedPosts(int $limit = 10, string $period = 'month'): Collection
    {
        $query = SearchClick::select('post_id', DB::raw('COUNT(*) as click_count'))
            ->with('post')
            ->groupBy('post_id')
            ->orderByDesc('click_count')
            ->limit($limit);

        if ($period) {
            $query->whereHas('searchLog', function ($q) use ($period) {
                $this->applyPeriodFilter($q, $period);
            });
        }

        return $query->get();
    }

    /**
     * Apply period filter to query
     */
    protected function applyPeriodFilter($query, string $period)
    {
        $date = match ($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subDay(),
        };

        return $query->where('created_at', '>=', $date);
    }
}
