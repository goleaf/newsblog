<?php

namespace App\Nova\Metrics;

use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

class DatabaseStatus extends Value
{
    /**
     * Get the displayable name of the metric.
     */
    public function name(): string
    {
        return 'Database Status';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): ValueResult
    {
        try {
            $pdo = DB::connection()->getPdo();
            $status = 'Connected';
            $color = 'green';

            // Get database size
            $dbName = DB::connection()->getDatabaseName();
            $size = DB::selectOne('
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = ?
            ', [$dbName]);

            $suffix = $size ? "{$size->size_mb} MB" : '';
        } catch (\Exception $e) {
            $status = 'Disconnected';
            $color = 'red';
            $suffix = '';
        }

        return $this->result($status)
            ->suffix($suffix)
            ->format(function ($value) use ($color) {
                return "<span class='text-{$color}-600 font-semibold'>{$value}</span>";
            });
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): ?DateTimeInterface
    {
        return now()->addMinutes(1);
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'database-status';
    }
}
