<?php

namespace App\View\Composers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class CategoryMenuComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $limit = $view->getData()['limit'] ?? null;

        // Cache categories for 1 hour (3600 seconds)
        $cacheKey = 'category_menu'.($limit ? "_{$limit}" : '');

        $categories = Cache::remember($cacheKey, 3600, function () use ($limit) {
            return Category::active()
                ->parent()
                ->ordered()
                ->withCount(['posts' => function ($query) {
                    $query->published();
                }])
                ->with([
                    'children' => function ($query) {
                        $query->active()->ordered()->withCount(['posts' => function ($q) {
                            $q->published();
                        }]);
                    },
                    'posts' => function ($query) {
                        $query->published()
                            ->latest()
                            ->limit(3)
                            ->select('id', 'title', 'slug', 'featured_image', 'category_id', 'published_at', 'reading_time');
                    },
                ])
                ->when($limit, fn ($query) => $query->limit($limit))
                ->get();
        });

        $view->with('categories', $categories);
    }
}
