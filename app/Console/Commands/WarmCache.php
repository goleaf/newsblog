<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use App\Services\CacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-populate application caches for faster first-page loads';

    public function __construct(protected CacheService $cacheService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Warming caches...');

        // Home view cache (HTML)
        Cache::forget(CacheService::PREFIX_VIEW.'.'.CacheService::PREFIX_HOME);

        // Homepage data components
        $this->cacheService->cacheQuery('home.featured', CacheService::TTL_LONG, function () {
            return Post::published()
                ->featured()
                ->with(['user:id,name', 'category:id,name,slug'])
                ->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'reading_time', 'view_count', 'user_id', 'category_id'])
                ->latest()
                ->take(3)
                ->get();
        });

        $this->cacheService->cacheQuery('home.trending', CacheService::TTL_MEDIUM, function () {
            return Post::published()
                ->trending()
                ->with(['user:id,name', 'category:id,name,slug'])
                ->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'reading_time', 'view_count', 'user_id', 'category_id'])
                ->latest()
                ->take(6)
                ->get();
        });

        // Popular categories (by posts_count, limited)
        $this->cacheService->cacheQuery('home.category-sections', CacheService::TTL_MEDIUM, function () {
            return Category::active()
                ->parents()
                ->ordered()
                ->withCount('posts')
                ->select(['id', 'name', 'slug', 'description', 'icon', 'color_code'])
                ->take(4)
                ->get();
        });

        // Category tree and menu items
        $this->cacheService->rememberCategoryTree(CacheService::TTL_VERY_LONG, function () {
            return Category::active()
                ->parents()
                ->with(['children' => function ($q) {
                    $q->active()->ordered();
                }])
                ->ordered()
                ->get();
        });

        $this->cacheService->rememberMenuItems('primary', CacheService::TTL_VERY_LONG, static function () {
            // Placeholder: load menu items via DB or config when available
            return [];
        });

        $this->info('Cache warming completed.');

        return self::SUCCESS;
    }
}


