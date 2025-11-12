<?php

namespace App\Nova;

use App\Nova\Filters\UserRole;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class User extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\User>
     */
    public static $model = \App\Models\User::class;

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
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'email',
    ];

    /**
     * Build an "index" query for the given resource.
     */
    public static function indexQuery(NovaRequest $request, Builder $query): Builder
    {
        return parent::indexQuery($request, $query->withCount(['posts', 'comments']));
    }

    /**
     * Build a "relatable" query for the given resource.
     *
     * This query determines which instances of the model may be attached to other resources.
     */
    public static function relatableQuery(NovaRequest $request, Builder $query): Builder
    {
        return parent::relatableQuery($request, $query->select(['id', 'name', 'email', 'role']));
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

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Email')
                ->sortable()
                ->rules('required', 'email', 'max:255')
                ->creationRules('unique:users,email')
                ->updateRules('unique:users,email,{{resourceId}}'),

            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', 'min:8')
                ->updateRules('nullable', 'min:8')
                ->help('Leave blank to keep current password'),

            Select::make('Role')
                ->options([
                    'admin' => 'Admin',
                    'editor' => 'Editor',
                    'author' => 'Author',
                    'user' => 'User',
                ])
                ->default('user')
                ->rules('required', 'in:admin,editor,author,user')
                ->sortable()
                ->displayUsingLabels(),

            Image::make('Avatar')
                ->disk('public')
                ->path('avatars')
                ->nullable()
                ->prunable()
                ->help('Profile picture'),

            Textarea::make('Bio')
                ->nullable()
                ->rows(4)
                ->rules('max:1000')
                ->hideFromIndex()
                ->help('Short biography or description'),

            Select::make('Status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'suspended' => 'Suspended',
                ])
                ->default('active')
                ->rules('required', 'in:active,inactive,suspended')
                ->sortable()
                ->displayUsingLabels(),

            DateTime::make('Email Verified At', 'email_verified_at')
                ->readonly()
                ->nullable()
                ->sortable()
                ->hideFromIndex()
                ->help('Date and time when email was verified'),

            DateTime::make('Created At', 'created_at')
                ->readonly()
                ->sortable()
                ->hideFromIndex(),

            HasMany::make('Posts', 'posts', Post::class),

            HasMany::make('Comments', 'comments', Comment::class),
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
            new UserRole,
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
