<?php

namespace App\Nova\Metrics;

use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

class StorageStatus extends Value
{
    /**
     * Get the displayable name of the metric.
     */
    public function name(): string
    {
        return 'Storage Status';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): ValueResult
    {
        try {
            $testFile = 'health_check_'.time().'.txt';
            Storage::put($testFile, 'health check');
            $canRead = Storage::exists($testFile);
            Storage::delete($testFile);

            if ($canRead) {
                $status = 'Accessible';
                $color = 'green';

                // Get file count
                $files = Storage::allFiles('public');
                $suffix = count($files).' files';
            } else {
                $status = 'Inaccessible';
                $color = 'red';
                $suffix = '';
            }
        } catch (\Exception $e) {
            $status = 'Inaccessible';
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
        return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'storage-status';
    }
}
