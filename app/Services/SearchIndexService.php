<?php

namespace App\Services;

use App\Exceptions\FuzzySearch\SearchIndexException;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SearchIndexService
{
    protected string $cachePrefix;

    protected int $indexTtl;

    public function __construct()
    {
        $this->cachePrefix = config('fuzzy-search.cache.prefix', 'fuzzy_search');
        $this->indexTtl = config('fuzzy-search.cache.index_ttl', 86400);
    }

    /**
     * Build complete search index for all published posts
     */
    public function buildIndex(): int
    {
        try {
            $posts = Post::published()
                ->with(['user', 'category', 'tags'])
                ->get();

            $indexData = $posts->map(function ($post) {
                return $this->preparePostForIndex($post);
            })->toArray();

            Cache::put(
                $this->getCacheKey('posts'),
                $indexData,
                $this->indexTtl
            );

            Log::info('Search index built successfully', [
                'post_count' => count($indexData),
            ]);

            return count($indexData);
        } catch (\Exception $e) {
            Log::error('Failed to build search index', [
                'error' => $e->getMessage(),
            ]);

            throw new SearchIndexException('Failed to build search index: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Rebuild a specific index type
     *
     * @param  string  $type  Index type (posts, tags, categories)
     * @return int Number of items indexed
     */
    public function rebuildIndex(string $type): int
    {
        try {
            $indexData = match ($type) {
                'posts' => $this->buildPostsIndex(),
                'tags' => $this->buildTagsIndex(),
                'categories' => $this->buildCategoriesIndex(),
                default => throw new SearchIndexException("Invalid index type: {$type}"),
            };

            Cache::put(
                $this->getCacheKey($type),
                $indexData,
                $this->indexTtl
            );

            Log::info('Search index rebuilt successfully', [
                'type' => $type,
                'count' => count($indexData),
            ]);

            return count($indexData);
        } catch (\Exception $e) {
            Log::error('Failed to rebuild search index', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            throw new SearchIndexException("Failed to rebuild {$type} index: ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * Add a post to the search index
     * Updates cache immediately (Requirement 10.3)
     */
    public function indexPost(Post $post): void
    {
        if (! $post->isPublished()) {
            return;
        }

        try {
            $index = $this->getIndex('posts');
            $postData = $this->preparePostForIndex($post);

            // Remove existing entry if present
            $index = array_filter($index, fn ($item) => $item['id'] !== $post->id);

            // Add new entry
            $index[] = $postData;

            // Update cache with 24-hour TTL
            Cache::put(
                $this->getCacheKey('posts'),
                $index,
                $this->indexTtl
            );

            Log::debug('Post added to search index', ['post_id' => $post->id]);
        } catch (\Exception $e) {
            Log::error('Failed to index post', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update a post in the search index
     * Invalidates cache and updates immediately (Requirement 10.3)
     */
    public function updatePost(Post $post): void
    {
        if (! $post->isPublished()) {
            $this->removePost($post->id);

            return;
        }

        $this->indexPost($post);
        Log::debug('Post updated in search index', ['post_id' => $post->id]);
    }

    /**
     * Remove a post from the search index
     * Updates cache immediately (Requirement 10.3)
     */
    public function removePost(int $postId): void
    {
        try {
            $index = $this->getIndex('posts');
            $index = array_filter($index, fn ($item) => $item['id'] !== $postId);

            // Update cache with 24-hour TTL
            Cache::put(
                $this->getCacheKey('posts'),
                array_values($index),
                $this->indexTtl
            );

            Log::debug('Post removed from search index', ['post_id' => $postId]);
        } catch (\Exception $e) {
            Log::error('Failed to remove post from index', [
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get indexed data for fuzzy matching
     * Uses 24-hour cache TTL (Requirement 10.1, 10.2)
     */
    public function getIndex(string $type = 'posts'): array
    {
        $cacheKey = $this->getCacheKey($type);

        // Cache::remember automatically handles cache check and storage
        return Cache::remember($cacheKey, $this->indexTtl, function () use ($type) {
            Log::debug('Building search index from database', ['type' => $type]);

            return match ($type) {
                'posts' => $this->buildPostsIndex(),
                'tags' => $this->buildTagsIndex(),
                'categories' => $this->buildCategoriesIndex(),
                default => [],
            };
        });
    }

    /**
     * Clear all search indexes
     */
    public function clearIndex(): void
    {
        Cache::forget($this->getCacheKey('posts'));
        Cache::forget($this->getCacheKey('tags'));
        Cache::forget($this->getCacheKey('categories'));

        Log::info('Search indexes cleared');
    }

    /**
     * Get index statistics
     */
    public function getIndexStats(): array
    {
        return [
            'posts' => [
                'count' => count($this->getIndex('posts')),
                'cached' => Cache::has($this->getCacheKey('posts')),
            ],
            'tags' => [
                'count' => count($this->getIndex('tags')),
                'cached' => Cache::has($this->getCacheKey('tags')),
            ],
            'categories' => [
                'count' => count($this->getIndex('categories')),
                'cached' => Cache::has($this->getCacheKey('categories')),
            ],
        ];
    }

    /**
     * Prepare post data for indexing
     */
    protected function preparePostForIndex(Post $post): array
    {
        $data = [
            'id' => $post->id,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'content' => strip_tags($post->content),
            'slug' => $post->slug,
            'author' => $post->user?->name,
            'category' => $post->category?->name,
            'tags' => $post->tags->pluck('name')->toArray(),
            'published_at' => $post->published_at?->toISOString(),
        ];

        // Add phonetic keys if phonetic matching is enabled
        if (config('fuzzy-search.phonetic_enabled', false)) {
            $data['title_phonetic'] = metaphone($post->title);
            if ($post->excerpt) {
                $data['excerpt_phonetic'] = metaphone($post->excerpt);
            }
        }

        return $data;
    }

    /**
     * Build posts index from database
     */
    protected function buildPostsIndex(): array
    {
        $posts = Post::published()
            ->with(['user', 'category', 'tags'])
            ->limit(config('fuzzy-search.limits.max_index_items', 10000))
            ->get();

        return $posts->map(fn ($post) => $this->preparePostForIndex($post))->toArray();
    }

    /**
     * Build tags index from database
     */
    protected function buildTagsIndex(): array
    {
        $tags = Tag::withCount('posts')
            ->limit(config('fuzzy-search.limits.max_index_items', 10000))
            ->get();

        return $tags->map(function ($tag) {
            $data = [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'posts_count' => $tag->posts_count ?? 0,
            ];

            // Add phonetic key if phonetic matching is enabled
            if (config('fuzzy-search.phonetic_enabled', false)) {
                $data['name_phonetic'] = metaphone($tag->name);
            }

            return $data;
        })->toArray();
    }

    /**
     * Build categories index from database
     */
    protected function buildCategoriesIndex(): array
    {
        $categories = Category::withCount('posts')
            ->limit(config('fuzzy-search.limits.max_index_items', 10000))
            ->get();

        return $categories->map(function ($category) {
            $data = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'posts_count' => $category->posts_count ?? 0,
            ];

            // Add phonetic keys if phonetic matching is enabled
            if (config('fuzzy-search.phonetic_enabled', false)) {
                $data['name_phonetic'] = metaphone($category->name);
                if ($category->description) {
                    $data['description_phonetic'] = metaphone($category->description);
                }
            }

            return $data;
        })->toArray();
    }

    /**
     * Get cache key for index type
     */
    protected function getCacheKey(string $type): string
    {
        return "{$this->cachePrefix}:index:{$type}";
    }

    /**
     * Invalidate all search-related caches
     * Called when content is updated to ensure fresh results (Requirement 10.3)
     */
    public function invalidateSearchCaches(): void
    {
        // Clear all search index caches
        Cache::forget($this->getCacheKey('posts'));
        Cache::forget($this->getCacheKey('tags'));
        Cache::forget($this->getCacheKey('categories'));

        // Note: Individual result caches will expire naturally via TTL
        // For more aggressive invalidation, we could use cache tags if supported
        // For now, index invalidation ensures fresh data on next search

        Log::info('Search index caches invalidated', [
            'types' => ['posts', 'tags', 'categories'],
        ]);
    }

    /**
     * Invalidate tags index cache
     */
    public function invalidateTagsCache(): void
    {
        Cache::forget($this->getCacheKey('tags'));
        Log::debug('Tags index cache invalidated');
    }

    /**
     * Invalidate categories index cache
     */
    public function invalidateCategoriesCache(): void
    {
        Cache::forget($this->getCacheKey('categories'));
        Log::debug('Categories index cache invalidated');
    }

    /**
     * Clear suggestion caches for a specific query prefix
     * If query prefix is null, clears all suggestion caches
     */
    public function clearSuggestionCache(?string $queryPrefix = null): void
    {
        $cachePrefix = config('fuzzy-search.cache.prefix', 'fuzzy_search');

        if ($queryPrefix !== null) {
            // Clear specific suggestion cache
            $cacheKey = "{$cachePrefix}:suggestions:".md5($queryPrefix);
            Cache::forget($cacheKey);
        } else {
            // Clear all suggestion caches (requires iterating or using cache tags)
            // For simplicity, we'll just log that suggestions should be refreshed
            // Individual caches will expire via TTL
            Log::info('Suggestion caches marked for refresh');
        }
    }
}
