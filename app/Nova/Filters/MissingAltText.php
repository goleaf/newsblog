<?php

namespace App\Nova\Filters;

use App\Models\Post;
use App\Services\AltTextValidator;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\BooleanFilter;

class MissingAltText extends BooleanFilter
{
    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Post>  $query
     * @param  array<string, bool>  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        $onlyMissing = (bool)($value['missing'] ?? false);
        if (! $onlyMissing) {
            return $query;
        }

        /** @var AltTextValidator $validator */
        $validator = app(AltTextValidator::class);

        $ids = [];
        Post::query()
            ->select(['id', 'content'])
            ->whereNotNull('content')
            ->orderBy('id')
            ->chunk(200, function ($chunk) use (&$ids, $validator) {
                foreach ($chunk as $post) {
                    $report = $validator->scanHtml((string)$post->content);
                    if ($report->missingAltCount > 0) {
                        $ids[] = $post->id;
                    }
                }
            });

        if (count($ids) === 0) {
            // Ensure no records if none match
            return $query->whereRaw('1=0');
        }

        return $query->whereIn('id', $ids);
    }

    /**
     * Get the filter's available options.
     *
     * @return array<string, string>
     */
    public function options(Request $request)
    {
        return [
            'Only posts with missing alt text' => 'missing',
        ];
    }
}



