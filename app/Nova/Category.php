<?php

namespace App\Nova;

use App\Nova\Filters\CategoryStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Color;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Category extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Category>
     */
    public static $model = \App\Models\Category::class;

    /**
     * The pagination per-page options used the resource index.
     *
     * @var array<int, int>
     */
    public static $perPageOptions = [50, 100, 150];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'description',
    ];

    /**
     * Build an "index" query for the given resource.
     */
    public static function indexQuery(NovaRequest $request, Builder $query): Builder
    {
        return parent::indexQuery($request, $query->with(['parent', 'children', 'posts'])->orderBy('display_order')->orderBy('name'));
    }

    /**
     * Build a "relatable" query for the given resource.
     *
     * This query determines which instances of the model may be attached to other resources.
     */
    public static function relatableQuery(NovaRequest $request, Builder $query): Builder
    {
        return parent::relatableQuery($request, $query->select(['id', 'name', 'slug', 'status'])->orderBy('display_order')->orderBy('name'));
    }

    /**
     * Determine if the current user can view any resources.
     */
    public static function authorizedToViewAny(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->can('viewAny', static::$model);
    }

    /**
     * Determine if the current user can view the given resource.
     */
    public function authorizedToView(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->can('view', $this->resource);
    }

    /**
     * Determine if the current user can create new resources.
     */
    public static function authorizedToCreate(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->can('create', static::$model);
    }

    /**
     * Determine if the current user can update the given resource.
     */
    public function authorizedToUpdate(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->can('update', $this->resource);
    }

    /**
     * Determine if the current user can delete the given resource.
     */
    public function authorizedToDelete(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->can('delete', $this->resource);
    }

    /**
     * Determine if the current user can delete any resources (bulk delete).
     */
    public static function authorizedToDeleteAny(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->can('delete', static::$model);
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field|\Laravel\Nova\Panel|\Laravel\Nova\ResourceTool|\Illuminate\Http\Resources\MergeValue>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Slug')
                ->readonly()
                ->help('Auto-generated from name'),

            Textarea::make('Description')
                ->nullable()
                ->rows(3)
                ->rules('max:1000')
                ->help('Brief description of the category'),

            BelongsTo::make('Parent Category', 'parent', Category::class)
                ->nullable()
                ->searchable()
                ->help('Select a parent category to create a subcategory')
                ->fillUsing(function (NovaRequest $request, $model, $attribute, $requestAttribute) {
                    $value = $request->input($requestAttribute) ?? $request->input('parent_id');

                    if ($value) {
                        $model->parent()->associate($value);
                    }
                }),

            Text::make('Icon')
                ->nullable()
                ->rules('max:255')
                ->help('Icon class (e.g., fa-newspaper, fa-code)'),

            Color::make('Color Code', 'color_code')
                ->nullable()
                ->help('Color for category display'),

            Select::make('Status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ])
                ->default('active')
                ->rules('required', 'in:active,inactive')
                ->sortable()
                ->displayUsingLabels(),

            Number::make('Display Order', 'display_order')
                ->default(0)
                ->sortable()
                ->rules('integer', 'min:0')
                ->help('Order in which categories are displayed (lower numbers first)'),

            \Laravel\Nova\Panel::make('SEO', [
                Text::make('Meta Title', 'meta_title')
                    ->nullable()
                    ->rules('max:255')
                    ->help('SEO meta title for search engines'),

                Textarea::make('Meta Description', 'meta_description')
                    ->nullable()
                    ->rows(2)
                    ->rules('max:500')
                    ->help('SEO meta description for search engines'),
            ]),

            HasMany::make('Child Categories', 'children', Category::class),

            HasMany::make('Posts', 'posts', Post::class),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array<int, \Laravel\Nova\Filters\Filter>
     */
    public function filters(NovaRequest $request): array
    {
        return [
            new CategoryStatus,
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array<int, \Laravel\Nova\Lenses\Lens>
     */
    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
