<?php

namespace App\Nova\Lenses;

use App\Models\Post as PostModel;
use App\Services\AltTextValidator;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Lenses\Lens;

class PostsMissingAltText extends Lens
{
    /**
     * Get the fields displayed by the lens.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make('ID', 'id')->sortable(),

            Text::make('Title')
                ->sortable(),

            BelongsTo::make('Author', 'user', \App\Nova\User::class),

            BelongsTo::make('Category', 'category', \App\Nova\Category::class),

            Number::make('Image Count', function () {
                /** @var AltTextValidator $validator */
                $validator = app(AltTextValidator::class);

                return $validator->scanHtml((string) ($this->content ?? ''))->totalImages;
            })->sortable(),

            Number::make('Missing Alt Count', function () {
                /** @var AltTextValidator $validator */
                $validator = app(AltTextValidator::class);

                return $validator->scanHtml((string) ($this->content ?? ''))->missingAltCount;
            })->sortable(),
        ];
    }

    /**
     * Get the cards available on the lens.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the filters available on the lens.
     *
     * @return array<int, \Laravel\Nova\Filters\Filter>
     */
    public function filters(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the actions available on the lens.
     *
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions(NovaRequest $request): array
    {
        return [
            new \App\Nova\Actions\FillMissingAltText,
        ];
    }

    /**
     * Build the query for the lens.
     */
    public static function query(NovaRequest $request, $query): Builder
    {
        /** @var AltTextValidator $validator */
        $validator = app(AltTextValidator::class);

        $ids = [];
        PostModel::query()
            ->select(['id', 'content'])
            ->whereNotNull('content')
            ->orderBy('id')
            ->chunk(200, function ($chunk) use (&$ids, $validator) {
                foreach ($chunk as $post) {
                    $report = $validator->scanHtml((string) $post->content);
                    if ($report->missingAltCount > 0) {
                        $ids[] = $post->id;
                    }
                }
            });

        $builder = $query->whereIn('id', $ids);

        return $request->withOrdering($request->withFilters($builder));
    }

    /**
     * Get the URI key for the lens.
     */
    public function uriKey(): string
    {
        return 'posts-missing-alt-text';
    }
}
