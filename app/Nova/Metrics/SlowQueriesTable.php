<?php

namespace App\Nova\Metrics;

use App\Services\PerformanceMetricsService;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\MetricTableRow;
use Laravel\Nova\Metrics\Table;

class SlowQueriesTable extends Table
{
	/**
	 * Calculate the value of the metric.
	 *
	 * @return array<int, \Laravel\Nova\Metrics\MetricTableRow>
	 */
	public function calculate(NovaRequest $request): array
	{
		$service = app(PerformanceMetricsService::class);
		$queries = $service->getSlowQueries(7);

		$rows = [];
		foreach ($queries as $q) {
			$rows[] = MetricTableRow::make()
				->title(number_format($q['time'], 2).' ms')
				->subtitle($q['sql']);
		}

		return $rows;
	}

	public function uriKey(): string
	{
		return 'slow-queries';
	}
}



