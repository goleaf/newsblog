<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    /**
     * Get all categories with caching.
     */
    public function getAllCategories(): Collection
    {
        $cacheKey = 'categories.all';
        $cacheTtl = config('cache.ttl.categories', 7200);

        return Cache::remember($cacheKey, $cacheTtl, function () {
            return Category::orderBy('name')
                ->withCount('posts')
                ->get();
        });
    }

    /**
     * Get root categories (no parent) with caching.
     */
    public function getRootCategories(): Collection
    {
        $cacheKey = 'categories.root';
        $cacheTtl = config('cache.ttl.categories', 7200);

        return Cache::remember($cacheKey, $cacheTtl, function () {
            return Category::whereNull('parent_id')
                ->orderBy('order')
                ->orderBy('name')
                ->withCount('posts')
                ->get();
        });
    }

    /**
     * Get category tree (hierarchical) with caching.
     */
    public function getCategoryTree(): Collection
    {
        $cacheKey = 'categories.tree';
        $cacheTtl = config('cache.ttl.categories', 7200);

        return Cache::remember($cacheKey, $cacheTtl, function () {
            return Category::whereNull('parent_id')
                ->with(['children' => function ($query) {
                    $query->orderBy('order')->orderBy('name');
                }])
                ->orderBy('order')
                ->orderBy('name')
                ->withCount('posts')
                ->get();
        });
    }

    /**
     * Get popular categories (by post count) with caching.
     */
    public function getPopularCategories(int $limit = 10): Collection
    {
        $cacheKey = "categories.popular.limit{$limit}";
        $cacheTtl = config('cache.ttl.categories', 7200);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($limit) {
            return Category::withCount('posts')
                ->having('posts_count', '>', 0)
                ->orderBy('posts_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get category by slug with caching.
     */
    public function getCategoryBySlug(string $slug): ?Category
    {
        $cacheKey = "category.slug.{$slug}";
        $cacheTtl = config('cache.ttl.categories', 7200);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($slug) {
            return Category::where('slug', $slug)
                ->withCount('posts')
                ->first();
        });
    }

    /**
     * Get category by ID with caching.
     */
    public function getCategoryById(int $id): ?Category
    {
        $cacheKey = "category.id.{$id}";
        $cacheTtl = config('cache.ttl.categories', 7200);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($id) {
            return Category::with(['parent', 'children'])
                ->withCount('posts')
                ->find($id);
        });
    }

    /**
     * Invalidate all category caches.
     */
    public function invalidateCache(): void
    {
        Cache::forget('categories.all');
        Cache::forget('categories.root');
        Cache::forget('categories.tree');

        // Invalidate popular categories cache
        for ($i = 5; $i <= 20; $i += 5) {
            Cache::forget("categories.popular.limit{$i}");
        }
    }

    /**
     * Invalidate cache for a specific category.
     */
    public function invalidateCategoryCache(Category $category): void
    {
        Cache::forget("category.id.{$category->id}");
        Cache::forget("category.slug.{$category->slug}");

        // Invalidate parent/child related caches
        $this->invalidateCache();
    }
}
