<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Feedback extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Feedback>
     */
    public static $model = \App\Models\Feedback::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'subject';

    /**
     * The columns that should be searched.
     *
     * @var array<int, string>
     */
    public static $search = [
        'subject',
        'message',
    ];

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
     * Determine if the current user can create resources.
     */
    public static function authorizedToCreate(Request $request): bool
    {
        return true; // All Nova users can submit feedback
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
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('User', 'user', User::class)
                ->nullable()
                ->searchable(),

            Select::make('Type')
                ->options([
                    'bug' => 'Bug Report',
                    'feature' => 'Feature Request',
                    'ux' => 'UX Improvement',
                    'general' => 'General Feedback',
                ])
                ->rules('required')
                ->displayUsingLabels()
                ->sortable(),

            Text::make('Subject')
                ->rules('required', 'max:255')
                ->sortable(),

            Textarea::make('Message')
                ->rules('required', 'max:5000')
                ->alwaysShow(),

            Select::make('Status')
                ->options([
                    'new' => 'New',
                    'reviewed' => 'Reviewed',
                    'resolved' => 'Resolved',
                    'closed' => 'Closed',
                ])
                ->default('new')
                ->displayUsingLabels()
                ->sortable()
                ->canSee(fn (NovaRequest $request) => $request->user()->role === 'admin'),

            Textarea::make('Admin Notes')
                ->nullable()
                ->alwaysShow()
                ->canSee(fn (NovaRequest $request) => $request->user()->role === 'admin'),

            BelongsTo::make('Reviewed By', 'reviewer', User::class)
                ->nullable()
                ->canSee(fn (NovaRequest $request) => $request->user()->role === 'admin'),

            DateTime::make('Reviewed At')
                ->nullable()
                ->canSee(fn (NovaRequest $request) => $request->user()->role === 'admin'),

            DateTime::make('Created At')
                ->sortable()
                ->onlyOnDetail(),

            DateTime::make('Updated At')
                ->sortable()
                ->onlyOnDetail(),
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
