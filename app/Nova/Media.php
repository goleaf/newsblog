<?php

namespace App\Nova;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Media extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Media>
     */
    public static $model = \App\Models\Media::class;

    /**
     * The pagination per-page options used the resource index.
     *
     * @var array<int, int>
     */
    public static $perPageOptions = [10, 25, 50];

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'file_name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'file_name', 'title', 'alt_text',
    ];

    /**
     * Build an "index" query for the given resource.
     */
    public static function indexQuery(NovaRequest $request, Builder $query): Builder
    {
        return parent::indexQuery($request, $query->with(['user']));
    }

    /**
     * Build a "relatable" query for the given resource.
     *
     * This query determines which instances of the model may be attached to other resources.
     */
    public static function relatableQuery(NovaRequest $request, Builder $query): Builder
    {
        return parent::relatableQuery($request, $query->select(['id', 'file_name', 'file_path', 'file_type']));
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
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field|\Laravel\Nova\Panel|\Laravel\Nova\ResourceTool|\Illuminate\Http\Resources\MergeValue>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Image::make('Thumbnail', 'file_path')
                ->disk('public')
                ->readonly()
                ->thumbnail(function ($value, $disk) {
                    if ($this->file_type === 'image') {
                        return $this->thumbnail_url ?? asset('storage/'.$value);
                    }

                    return null;
                })
                ->preview(function ($value, $disk) {
                    if ($this->file_type === 'image') {
                        return asset('storage/'.$value);
                    }

                    return null;
                })
                ->help('Preview of the media file'),

            Text::make('File Name', 'file_name')
                ->readonly()
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('File Path', 'file_path')
                ->readonly()
                ->hideFromIndex()
                ->help('Storage path of the file'),

            Text::make('File Type', 'file_type')
                ->readonly()
                ->sortable()
                ->displayUsing(function ($value) {
                    $badges = [
                        'image' => '🖼️ Image',
                        'document' => '📄 Document',
                        'video' => '🎥 Video',
                    ];

                    return $badges[$value] ?? ucfirst($value);
                })
                ->help('Type of media file'),

            Text::make('File Size', 'file_size')
                ->readonly()
                ->sortable()
                ->displayUsing(function ($value) {
                    return $this->size_human_readable;
                })
                ->help('Size of the file'),

            Text::make('MIME Type', 'mime_type')
                ->readonly()
                ->hideFromIndex()
                ->help('MIME type of the file'),

            Text::make('Alt Text', 'alt_text')
                ->rules('required_if:file_type,image', 'max:255')
                ->help('Alternative text for accessibility (required for images)'),

            Text::make('Title', 'title')
                ->nullable()
                ->sortable()
                ->rules('max:255')
                ->help('Title of the media file'),

            Textarea::make('Caption', 'caption')
                ->nullable()
                ->rows(3)
                ->hideFromIndex()
                ->help('Caption or description of the media'),

            BelongsTo::make('Uploaded By', 'user', User::class)
                ->searchable()
                ->sortable()
                ->readonly()
                ->default(function ($request) {
                    return $request->user()?->id;
                }),

            DateTime::make('Created At', 'created_at')
                ->readonly()
                ->sortable()
                ->hideFromIndex(),
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
            new Filters\MediaType,
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
