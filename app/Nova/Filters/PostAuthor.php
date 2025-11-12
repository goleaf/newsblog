<?php

namespace App\Nova\Filters;

use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class PostAuthor extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * Apply the filter to the given query.
     */
    public function apply(NovaRequest $request, Builder $query, mixed $value): Builder
    {
        return $query->where('user_id', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @return array<int, string>
     */
    public function options(NovaRequest $request): array
    {
        return User::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
