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
     * Add a post to the search index
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

            Cache::put(
                $this->getCacheKey('posts'),
                $index,
                $this->indexTtl
            );
        } catch (\Exception $e) {
            Log::error('Failed to index post', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update a post in the search index
     */
    public function updatePost(Post $post): void
    {
        if (! $post->isPublished()) {
            $this->removePost($post->id);

            return;
        }

        $this->indexPost($post);
    }

    /**
     * Remove a post from the search index
     */
    public function removePost(int $postId): void
    {
        try {
            $index = $this->getIndex('posts');
            $index = array_filter($index, fn ($item) => $item['id'] !== $postId);

            Cache::put(
                $this->getCacheKey('posts'),
                array_values($index),
                $this->indexTtl
            );
        } catch (\Exception $e) {
            Log::error('Failed to remove post from index', [
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get indexed data for fuzzy matching
     */
    public function getIndex(string $type = 'posts'): array
    {
        $cacheKey = $this->getCacheKey($type);

        return Cache::remember($cacheKey, $this->indexTtl, function () use ($type) {
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
        return [
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
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'posts_count' => $tag->posts_count ?? 0,
            ];
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
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'posts_count' => $category->posts_count ?? 0,
            ];
        })->toArray();
    }

    /**
     * Get cache key for index type
     */
    protected function getCacheKey(string $type): string
    {
        return "{$this->cachePrefix}:index:{$type}";
    }
}
