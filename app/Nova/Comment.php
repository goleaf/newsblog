<?php

namespace App\Nova;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Comment extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Comment>
     */
    public static $model = \App\Models\Comment::class;

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
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'content', 'author_name', 'author_email',
    ];

    /**
     * Build an "index" query for the given resource.
     */
    public static function indexQuery(NovaRequest $request, Builder $query): Builder
    {
        return parent::indexQuery($request, $query->with(['post', 'user', 'parent']));
    }

    /**
     * Build a "relatable" query for the given resource.
     *
     * This query determines which instances of the model may be attached to other resources.
     */
    public static function relatableQuery(NovaRequest $request, Builder $query): Builder
    {
        return parent::relatableQuery($request, $query->select(['id', 'content', 'status']));
    }

    /**
     * Determine if the current user can view any resources.
     */
    public static function authorizedToViewAny(Request $request): bool
    {
        return $request->user()->can('viewAny', static::$model);
    }

    /**
     * Determine if the current user can view the given resource.
     */
    public function authorizedToView(Request $request): bool
    {
        return $request->user()->can('view', $this->resource);
    }

    /**
     * Determine if the current user can create new resources.
     */
    public static function authorizedToCreate(Request $request): bool
    {
        return $request->user()->can('create', static::$model);
    }

    /**
     * Determine if the current user can update the given resource.
     */
    public function authorizedToUpdate(Request $request): bool
    {
        return $request->user()->can('update', $this->resource);
    }

    /**
     * Determine if the current user can delete the given resource.
     */
    public function authorizedToDelete(Request $request): bool
    {
        return $request->user()->can('delete', $this->resource);
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

            BelongsTo::make('Post', 'post', Post::class)
                ->searchable()
                ->sortable()
                ->rules('required')
                ->withMeta(['extraAttributes' => [
                    'readonly' => $request->isUpdateOrUpdateAttachedRequest(),
                ]]),

            BelongsTo::make('User', 'user', User::class)
                ->searchable()
                ->sortable()
                ->nullable()
                ->help('Leave empty for guest comments'),

            Text::make('Author Name', 'author_name')
                ->nullable()
                ->rules('max:255')
                ->help('Name for guest comments (when user is not set)')
                ->hideFromIndex(),

            Text::make('Author Email', 'author_email')
                ->nullable()
                ->rules('email', 'max:255')
                ->help('Email for guest comments (when user is not set)')
                ->hideFromIndex(),

            Textarea::make('Content')
                ->rules('required')
                ->rows(4)
                ->alwaysShow(),

            Select::make('Status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'spam' => 'Spam',
                ])
                ->default('pending')
                ->rules('required', 'in:pending,approved,spam')
                ->sortable()
                ->displayUsingLabels(),

            Text::make('IP Address', 'ip_address')
                ->readonly()
                ->hideFromIndex()
                ->help('IP address of the commenter'),

            Text::make('User Agent', 'user_agent')
                ->readonly()
                ->hideFromIndex()
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->help('Browser user agent string'),

            BelongsTo::make('Parent Comment', 'parent', Comment::class)
                ->nullable()
                ->searchable()
                ->hideFromIndex()
                ->help('Parent comment for replies'),

            HasMany::make('Replies', 'replies', Comment::class),

            DateTime::make('Created At', 'created_at')
                ->readonly()
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),
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
            new Filters\CommentStatus,
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
        return [
            new Actions\ApproveComments,
            new Actions\RejectComments,
        ];
    }
}
