<?php

namespace App\Nova\Metrics;

use App\Services\PerformanceMetricsService;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

class CacheHitRatio extends Trend
{
	/**
	 * Calculate the value of the metric.
	 */
	public function calculate(NovaRequest $request): TrendResult
	{
		$service = app(PerformanceMetricsService::class);
		$data = $service->getCacheStats(); // ['date','hits','misses','ratio']
		$trend = [];
		foreach ($data as $row) {
			$trend[$row['date']] = $row['ratio'];
		}

		return (new TrendResult)->trend($trend)->showLatestValue();
	}

	public function ranges(): array
	{
		return [
			7 => '7 Days',
		];
	}

	public function uriKey(): string
	{
		return 'cache-hit-ratio';
	}
}



