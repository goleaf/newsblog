<?php

namespace App\Nova\Metrics;

use App\Services\SystemHealthService;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Metric;

class SystemHealthOverview extends Metric
{
    /**
     * Get the displayable name of the metric.
     */
    public function name(): string
    {
        return 'System Health Overview';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): mixed
    {
        $healthService = app(SystemHealthService::class);
        $health = $healthService->getCachedHealth();

        return $this->result($health['status'])
            ->format(function ($value) use ($health) {
                $statusColor = match ($health['status']) {
                    'healthy' => 'text-green-600',
                    'degraded' => 'text-yellow-600',
                    'unhealthy' => 'text-red-600',
                    default => 'text-gray-600',
                };

                $statusIcon = match ($health['status']) {
                    'healthy' => '✓',
                    'degraded' => '⚠',
                    'unhealthy' => '✗',
                    default => '?',
                };

                $details = collect($health['checks'])
                    ->map(fn ($check, $name) => ucfirst($name).': '.$check['status'])
                    ->join(' | ');

                return "<div class='text-center'>
                    <div class='{$statusColor} text-4xl font-bold mb-2'>{$statusIcon}</div>
                    <div class='text-xl font-semibold mb-2'>System {$value}</div>
                    <div class='text-sm text-gray-600'>{$details}</div>
                </div>";
            });
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'system-health-overview';
    }
}
