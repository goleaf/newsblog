<?php

namespace App\Nova\Metrics;

use App\Services\PerformanceMetricsService;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

class MemoryUsage extends Value
{
	/**
	 * Calculate the value of the metric.
	 */
	public function calculate(NovaRequest $request): ValueResult
	{
		$service = app(PerformanceMetricsService::class);
		$memory = $service->getMemoryUsage();

		return $this->result($memory['percentage'])
			->suffix('%')
			->help($memory['usage_formatted'].' / '.$memory['limit_formatted']);
	}

	/**
	 * Get the URI key for the metric.
	 */
	public function uriKey(): string
	{
		return 'memory-usage';
	}
}



