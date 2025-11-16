<?php

namespace App\Nova\Metrics;

use App\Services\PerformanceMetricsService;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

class AveragePageLoad extends Trend
{
	/**
	 * Calculate the value of the metric.
	 */
	public function calculate(NovaRequest $request): TrendResult
	{
		$service = app(PerformanceMetricsService::class);
		$data = $service->getAveragePageLoadTime(); // array of ['hour' => 'Y-m-d H:00', 'average' => float]
		$trend = [];
		foreach ($data as $row) {
			$trend[$row['hour']] = $row['average'];
		}

		return (new TrendResult)->trend($trend)->showLatestValue();
	}

	/**
	 * Get the ranges available for the metric.
	 */
	public function ranges(): array
	{
		// Data source is fixed to 24 hours; present a single option
		return [
			24 => '24 Hours',
		];
	}

	/**
	 * Get the URI key for the metric.
	 */
	public function uriKey(): string
	{
		return 'avg-page-load';
	}
}


