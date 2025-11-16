<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdvancedSearchService
{
    /**
     * Search posts with advanced filters.
     *
     * Implements Requirements 39.1-39.5:
     * - Date range filtering
     * - Author dropdown filter
     * - Category filter with subcategory inclusion
     * - Tag multi-select filter
     * - Filter combination with AND logic
     *
     * @param  string  $query  The search query
     * @param  array  $filters  Advanced filters (date_from, date_to, author, category, tags, sort)
     * @param  int  $perPage  Results per page (default: 15)
     */
    public function search(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $queryBuilder = $this->buildAdvancedSearchQuery($query, $filters);

        $posts = $queryBuilder->paginate($perPage);

        // Add highlighting to results
        $posts->getCollection()->transform(function ($post) use ($query) {
            $post->highlighted_title = $this->highlightMatches($post->title, $query);
            $post->highlighted_excerpt = $this->highlightMatches($post->excerpt ?? '', $query);

            return $post;
        });

        return $posts;
    }

    /**
     * Build the advanced search query with all filters.
     */
    protected function buildAdvancedSearchQuery(string $query, array $filters = []): Builder
    {
        $queryBuilder = Post::query()
            ->where('status', \App\Enums\PostStatus::Published)
            ->with(['user', 'category', 'tags']);

        // Multi-field search (title, content, excerpt)
        if (! empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%");
            });
        }

        // Apply date range filter (Requirement 39.1)
        if (! empty($filters['date_from'])) {
            $queryBuilder->whereDate('published_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $nextDayStart = Carbon::parse($filters['date_to'])->addDay()->startOfDay();
            $queryBuilder->where('published_at', '<', $nextDayStart);
        }

        // Apply author filter (Requirement 39.2)
        if (! empty($filters['author'])) {
            $queryBuilder->where('user_id', $filters['author']);
        }

        // Apply category filter with subcategory inclusion (Requirement 39.3)
        if (! empty($filters['category'])) {
            $categoryIds = $this->getCategoryWithSubcategories($filters['category']);
            $queryBuilder->whereIn('category_id', $categoryIds);
        }

        // Apply tag multi-select filter with AND logic (Requirement 39.4)
        if (! empty($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tagId) {
                $queryBuilder->whereHas('tags', function ($q) use ($tagId) {
                    $q->where('tags.id', $tagId);
                });
            }
        }

        // Apply sorting (Requirement 5.2, 14.3)
        $sort = $filters['sort'] ?? 'newest';

        switch ($sort) {
            case 'oldest':
                $queryBuilder->oldest('published_at');
                break;

            case 'popular':
                $queryBuilder->orderByDesc('view_count');
                break;

            case 'trending':
                // Trending: high views in recent period + reactions
                $queryBuilder->orderByRaw('(view_count * 0.7 + COALESCE((SELECT COUNT(*) FROM reactions WHERE reactions.post_id = posts.id), 0) * 0.3) DESC');
                break;

            case 'relevant':
                // Relevance-based sorting (exact title matches first)
                if (! empty($query)) {
                    $queryBuilder->orderByRaw('
                        CASE 
                            WHEN title LIKE ? THEN 1
                            WHEN title LIKE ? THEN 2
                            WHEN excerpt LIKE ? THEN 3
                            ELSE 4
                        END
                    ', [
                        $query, // Exact match
                        "%{$query}%", // Contains match
                        "%{$query}%", // Excerpt match
                    ]);
                }
                $queryBuilder->latest('published_at');
                break;

            case 'newest':
            default:
                $queryBuilder->latest('published_at');
                break;
        }

        return $queryBuilder;
    }

    /**
     * Get category ID and all its subcategory IDs.
     */
    protected function getCategoryWithSubcategories(int $categoryId): array
    {
        $category = Category::find($categoryId);

        if (! $category) {
            return [$categoryId];
        }

        $categoryIds = [$categoryId];

        // Get all child categories recursively
        $children = $this->getAllChildCategories($category);
        $categoryIds = array_merge($categoryIds, $children->pluck('id')->toArray());

        return $categoryIds;
    }

    /**
     * Recursively get all child categories.
     */
    protected function getAllChildCategories(Category $category): Collection
    {
        $children = collect();

        foreach ($category->children as $child) {
            $children->push($child);
            $children = $children->merge($this->getAllChildCategories($child));
        }

        return $children;
    }

    /**
     * Highlight matching terms in text.
     */
    protected function highlightMatches(string $text, string $query): string
    {
        if (empty($query) || empty($text)) {
            return e($text);
        }

        // Split query into words
        $words = preg_split('/\s+/', trim($query), -1, PREG_SPLIT_NO_EMPTY);

        // Escape text first
        $escapedText = e($text);

        // Highlight each word
        foreach ($words as $word) {
            $escapedWord = preg_quote($word, '/');
            $escapedText = preg_replace(
                '/('.$escapedWord.')/iu',
                '<mark class="search-highlight">$1</mark>',
                $escapedText
            );
        }

        return $escapedText;
    }

    /**
     * Get all authors who have published posts.
     *
     * Implements Requirement 39.2: Display dropdown of all authors with published posts
     */
    public function getAuthorsWithPosts(): Collection
    {
        return User::query()
            ->whereHas('posts', function ($q) {
                $q->where('status', 'published');
            })
            ->withCount(['posts' => function ($q) {
                $q->where('status', 'published');
            }])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Get all active categories.
     */
    public function getCategories(): Collection
    {
        return Category::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'parent_id']);
    }

    /**
     * Get all tags that have posts.
     */
    public function getTagsWithPosts(): Collection
    {
        return Tag::query()
            ->whereHas('posts', function ($q) {
                $q->where('status', 'published');
            })
            ->withCount(['posts' => function ($q) {
                $q->where('status', 'published');
            }])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Count active filters.
     *
     * Implements Requirement 39.5: Display active filter count
     */
    public function countActiveFilters(array $filters): int
    {
        $count = 0;

        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $count++;
        }

        if (! empty($filters['author'])) {
            $count++;
        }

        if (! empty($filters['category'])) {
            $count++;
        }

        if (! empty($filters['tags']) && is_array($filters['tags']) && count($filters['tags']) > 0) {
            $count++;
        }

        return $count;
    }

    /**
     * Count total search results without pagination.
     */
    public function countResults(string $query, array $filters = []): int
    {
        return $this->buildAdvancedSearchQuery($query, $filters)->count();
    }
}
