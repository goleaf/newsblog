<?php

namespace App\Nova;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class ActivityLog extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\ActivityLog>
     */
    public static $model = \App\Models\ActivityLog::class;

    /**
     * The pagination per-page options used the resource index.
     *
     * @var array<int, int>
     */
    public static $perPageOptions = [25, 50, 100];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'description';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'description', 'log_name',
    ];

    /**
     * Build an "index" query for the given resource.
     */
    public static function indexQuery(NovaRequest $request, Builder $query): Builder
    {
        return parent::indexQuery($request, $query->with(['subject', 'causer']));
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
     * Activity logs are system-generated only.
     */
    public static function authorizedToCreate(Request $request): bool
    {
        return false;
    }

    /**
     * Determine if the current user can update the given resource.
     * Activity logs are read-only.
     */
    public function authorizedToUpdate(Request $request): bool
    {
        return false;
    }

    /**
     * Determine if the current user can delete the given resource.
     * Activity logs are read-only.
     */
    public function authorizedToDelete(Request $request): bool
    {
        return false;
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

            Text::make('Log Name', 'log_name')
                ->sortable()
                ->readonly(),

            Textarea::make('Description')
                ->rows(2)
                ->readonly(),

            Text::make('Event')
                ->sortable()
                ->readonly(),

            MorphTo::make('Subject')
                ->types([
                    Post::class,
                    User::class,
                    Category::class,
                    Tag::class,
                    Comment::class,
                    Media::class,
                    Page::class,
                    Newsletter::class,
                    Setting::class,
                ])
                ->nullable()
                ->readonly(),

            MorphTo::make('Causer')
                ->types([
                    User::class,
                ])
                ->nullable()
                ->readonly(),

            Code::make('Properties')
                ->json()
                ->nullable()
                ->readonly()
                ->hideFromIndex()
                ->help('Before and after values for modified fields'),

            Text::make('IP Address', 'ip_address')
                ->readonly()
                ->hideFromIndex()
                ->help('IP address of the user who performed the action'),

            Text::make('User Agent', 'user_agent')
                ->readonly()
                ->hideFromIndex()
                ->help('Browser user agent string'),

            DateTime::make('Created At', 'created_at')
                ->sortable()
                ->readonly()
                ->displayUsing(function ($value) {
                    return $value?->format('Y-m-d H:i:s');
                }),
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
        return [];
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
