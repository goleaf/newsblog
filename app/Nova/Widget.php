<?php

namespace App\Nova;

use App\Models\Widget as WidgetModel;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\KeyValue;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Widget extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Widget>
     */
    public static $model = WidgetModel::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array<int, string>
     */
    public static $search = [
        'id', 'title', 'type',
    ];

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(Request $request): array
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make(__('Widget Area'), 'widgetArea', WidgetArea::class)->sortable()->searchable()->required(),
            Text::make(__('Title'), 'title')->rules('required', 'max:255')->sortable(),
            Select::make(__('Type'), 'type')->options([
                'recent-posts' => __('Recent Posts'),
                'popular-posts' => __('Popular Posts'),
                'categories' => __('Categories'),
                'tags-cloud' => __('Tags Cloud'),
                'newsletter' => __('Newsletter'),
                'search' => __('Search'),
                'custom-html' => __('Custom HTML'),
                'who-to-follow' => __('Who To Follow'),
            ])->displayUsingLabels()->rules('required')->sortable(),
            KeyValue::make(__('Settings'), 'settings')->rules('nullable', 'array')->keyLabel(__('Key'))->valueLabel(__('Value'))->actionText(__('Add Setting')),
            Number::make(__('Order'), 'order')->min(0)->step(1)->sortable(),
            Boolean::make(__('Active'), 'active')->trueValue(true)->falseValue(false)->sortable(),
        ];
    }

    /**
     * Get the cards available for the request.
     */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     */
    public function filters(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     */
    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     */
    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
