<?php

namespace App\Nova\Metrics;

use DateTimeInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

class RedisStatus extends Value
{
    /**
     * Get the displayable name of the metric.
     */
    public function name(): string
    {
        return 'Redis Status';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): ValueResult
    {
        try {
            Cache::store('redis')->put('health_check', true, 10);
            $connected = Cache::store('redis')->get('health_check');

            if ($connected) {
                $status = 'Connected';
                $color = 'green';

                // Get Redis info
                $info = Redis::connection()->info();
                $memory = isset($info['used_memory_human']) ? $info['used_memory_human'] : '';
                $suffix = $memory ? "Memory: {$memory}" : '';
            } else {
                $status = 'Disconnected';
                $color = 'red';
                $suffix = '';
            }
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
        return 'redis-status';
    }
}
