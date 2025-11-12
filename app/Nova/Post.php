<?php

namespace App\Nova;

use App\Nova\Filters\DateRange;
use App\Nova\Filters\PostAuthor;
use App\Nova\Filters\PostCategory;
use App\Nova\Filters\PostFeatured;
use App\Nova\Filters\PostStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;

class Post extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Post>
     */
    public static $model = \App\Models\Post::class;

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
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'title', 'excerpt', 'content',
    ];

    /**
     * Build an "index" query for the given resource.
     */
    public static function indexQuery(NovaRequest $request, Builder $query): Builder
    {
        return parent::indexQuery($request, $query->with(['user', 'category', 'tags']));
    }

    /**
     * Build a "relatable" query for the given resource.
     *
     * This query determines which instances of the model may be attached to other resources.
     */
    public static function relatableQuery(NovaRequest $request, Builder $query): Builder
    {
        return parent::relatableQuery($request, $query->select(['id', 'title', 'slug', 'status']));
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

            Text::make('Title')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Slug')
                ->readonly()
                ->hideFromIndex()
                ->help('Auto-generated from title'),

            Textarea::make('Excerpt')
                ->nullable()
                ->rows(3)
                ->rules('max:500')
                ->help('Brief summary of the post'),

            Trix::make('Content')
                ->rules('required')
                ->withFiles('public')
                ->hideFromIndex(),

            Image::make('Featured Image', 'featured_image')
                ->disk('public')
                ->path('posts')
                ->nullable()
                ->prunable(),

            Text::make('Image Alt Text', 'image_alt_text')
                ->nullable()
                ->hideFromIndex()
                ->rules('max:255')
                ->help('Alternative text for the featured image'),

            BelongsTo::make('Author', 'user', User::class)
                ->searchable()
                ->sortable()
                ->rules('required')
                ->readonly(function ($request) {
                    return $request->isUpdateOrUpdateAttachedRequest();
                })
                ->default(function ($request) {
                    return $request->user()->id;
                }),

            BelongsTo::make('Category', 'category', Category::class)
                ->searchable()
                ->sortable()
                ->rules('required')
                ->nullable(),

            BelongsToMany::make('Tags', 'tags', Tag::class)
                ->searchable(),

            Select::make('Status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'scheduled' => 'Scheduled',
                ])
                ->default('draft')
                ->rules('required', 'in:draft,published,scheduled')
                ->sortable()
                ->displayUsingLabels(),

            Boolean::make('Is Featured', 'is_featured')
                ->default(false)
                ->sortable(),

            Boolean::make('Is Trending', 'is_trending')
                ->default(false)
                ->sortable(),

            DateTime::make('Published At', 'published_at')
                ->nullable()
                ->sortable()
                ->help('Date and time when the post was published'),

            DateTime::make('Scheduled At', 'scheduled_at')
                ->nullable()
                ->sortable()
                ->hideFromIndex()
                ->dependsOn(['status'], function (DateTime $field, NovaRequest $request, $formData) {
                    if ($formData->status === 'scheduled') {
                        $field->show()->rules('required');
                    } else {
                        $field->hide();
                    }
                })
                ->help('Date and time when the post should be published'),

            Number::make('Reading Time', 'reading_time')
                ->readonly()
                ->sortable()
                ->hideFromIndex()
                ->help('Estimated reading time in minutes'),

            Number::make('View Count', 'view_count')
                ->readonly()
                ->sortable()
                ->default(0),

            \Laravel\Nova\Panel::make('SEO', [
                Text::make('Meta Title', 'meta_title')
                    ->nullable()
                    ->rules('max:70')
                    ->help('SEO meta title for search engines (max 70 characters for optimal display)'),

                Textarea::make('Meta Description', 'meta_description')
                    ->nullable()
                    ->rows(2)
                    ->rules('max:160')
                    ->help('SEO meta description for search engines (max 160 characters)'),

                Text::make('Meta Keywords', 'meta_keywords')
                    ->nullable()
                    ->rules('max:255')
                    ->help('Comma-separated keywords for SEO'),
            ]),

            HasMany::make('Comments', 'comments', Comment::class),

            // HasMany::make('Revisions', 'revisions', \App\Nova\PostRevision::class),
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
            new PostStatus,
            new PostCategory,
            new PostAuthor,
            new PostFeatured,
            new DateRange,
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
            new Actions\PublishPosts,
            new Actions\FeaturePosts,
            new Actions\ExportPosts,
        ];
    }
}
