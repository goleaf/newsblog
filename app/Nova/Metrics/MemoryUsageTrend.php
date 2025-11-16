<?php

namespace App\Nova\Metrics;

use App\Services\PerformanceMetricsService;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

class MemoryUsageTrend extends Trend
{
    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): TrendResult
    {
        $service = app(PerformanceMetricsService::class);
        $data = $service->getAverageMemoryUsage(); // ['hour','average_mb']
        $trend = [];
        foreach ($data as $row) {
            $trend[$row['hour']] = $row['average_mb'];
        }

        return (new TrendResult)->trend($trend)->showLatestValue();
    }

    /**
     * Get the ranges available for the metric.
     */
    public function ranges(): array
    {
        return [
            24 => '24 Hours',
        ];
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'memory-usage-trend';
    }
}
