<?php

namespace App\Nova;

use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class PostRevision extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\PostRevision>
     */
    public static $model = \App\Models\PostRevision::class;

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
        'id', 'title', 'revision_note',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field|\Laravel\Nova\Panel|\Laravel\Nova\ResourceTool|\Illuminate\Http\Resources\MergeValue>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Post', 'post', Post::class)->sortable(),
            BelongsTo::make('User', 'user', User::class)->sortable(),

            Text::make('Title')
                ->onlyOnDetail(),

            Textarea::make('Excerpt')
                ->onlyOnDetail()
                ->alwaysShow(),

            Textarea::make('Content')
                ->onlyOnDetail()
                ->alwaysShow(),

            Text::make('Revision Note', 'revision_note')
                ->sortable()
                ->onlyOnIndex(),

            DateTime::make('Created At', 'created_at')
                ->sortable(),
        ];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions(NovaRequest $request): array
    {
        return [
            new Actions\RestoreRevision,
        ];
    }
}
