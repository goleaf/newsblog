<?php

namespace App\Nova\Filters;

use App\Models\Category;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class PostCategory extends Filter
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
        return $query->where('category_id', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @return array<int, string>
     */
    public function options(NovaRequest $request): array
    {
        return Category::query()
            ->orderBy('display_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
