<?php

namespace App\Nova\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class DateRange extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'date-filter';

    /**
     * Apply the filter to the given query.
     */
    public function apply(NovaRequest $request, Builder $query, mixed $value): Builder
    {
        if (empty($value)) {
            return $query;
        }

        $date = Carbon::parse($value)->startOfDay();

        return $query->whereDate('published_at', $date);
    }

    /**
     * Get the filter's available options.
     *
     * @return array<string, string>
     */
    public function options(NovaRequest $request): array
    {
        return [];
    }
}
