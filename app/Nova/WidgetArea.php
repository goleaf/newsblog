<?php

namespace App\Nova;

use App\Models\WidgetArea as WidgetAreaModel;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class WidgetArea extends Resource
{
	/**
	 * The model the resource corresponds to.
	 *
	 * @var class-string<\App\Models\WidgetArea>
	 */
	public static $model = WidgetAreaModel::class;

	/**
	 * The single value that should be used to represent the resource when being displayed.
	 *
	 * @var string
	 */
	public static $title = 'name';

	/**
	 * The columns that should be searched.
	 *
	 * @var array<int, string>
	 */
	public static $search = [
		'id', 'name', 'slug',
	];

	/**
	 * Get the fields displayed by the resource.
	 */
	public function fields(Request $request): array
	{
		return [
			ID::make()->sortable(),
			Text::make(__('Name'), 'name')->rules('required', 'max:255')->sortable(),
			Slug::make(__('Slug'), 'slug')->from('name')->rules('required', 'max:255')->sortable(),
			Textarea::make(__('Description'), 'description')->alwaysShow(),
			HasMany::make(__('Widgets'), 'widgets', Widget::class),
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


