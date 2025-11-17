<?php

namespace App\Nova\Metrics;

use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

class QueueStatus extends Value
{
    /**
     * Get the displayable name of the metric.
     */
    public function name(): string
    {
        return 'Queue Status';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): ValueResult
    {
        try {
            // Get pending jobs count
            $pendingJobs = DB::table('jobs')->count();

            // Get failed jobs count
            $failedJobs = DB::table('failed_jobs')->count();

            $status = $pendingJobs;
            $suffix = "Pending | {$failedJobs} Failed";

            $color = match (true) {
                $pendingJobs > 1000 => 'red',
                $pendingJobs > 500 => 'yellow',
                default => 'green',
            };
        } catch (\Exception $e) {
            $status = 'Error';
            $suffix = '';
            $color = 'red';
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
        return 'queue-status';
    }
}
