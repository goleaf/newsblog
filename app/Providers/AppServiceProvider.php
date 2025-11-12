<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Feedback;
use App\Models\Media;
use App\Models\Newsletter;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Observers\CategoryObserver;
use App\Observers\PostObserver;
use App\Observers\TagObserver;
use App\Policies\ActivityLogPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CommentPolicy;
use App\Policies\FeedbackPolicy;
use App\Policies\MediaPolicy;
use App\Policies\NewsletterPolicy;
use App\Policies\PagePolicy;
use App\Policies\PostPolicy;
use App\Policies\SettingPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use App\Services\BreadcrumbService;
use App\Services\FuzzySearchService;
use App\Services\SearchAnalyticsService;
use App\Services\SearchIndexService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register SearchIndexService as singleton to maintain index state
        $this->app->singleton(SearchIndexService::class, function ($app) {
            return new SearchIndexService;
        });

        // Register SearchAnalyticsService
        $this->app->bind(SearchAnalyticsService::class, function ($app) {
            return new SearchAnalyticsService;
        });

        // Register FuzzySearchService with dependency injection
        $this->app->bind(FuzzySearchService::class, function ($app) {
            return new FuzzySearchService(
                $app->make(SearchIndexService::class),
                $app->make(SearchAnalyticsService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Post::observe(PostObserver::class);
        Tag::observe(TagObserver::class);
        Category::observe(CategoryObserver::class);

        // Register authorization policies
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy(Newsletter::class, NewsletterPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
        Gate::policy(Feedback::class, FeedbackPolicy::class);

        // Track slow queries for performance monitoring
        if (config('app.debug')) {
            \Illuminate\Support\Facades\DB::listen(function ($query) {
                $performanceMetrics = app(\App\Services\PerformanceMetricsService::class);
                $performanceMetrics->logSlowQuery(
                    $query->sql,
                    $query->time,
                    $query->bindings
                );
            });
        }

        // Share breadcrumbs with all views
        View::composer('*', function ($view) {
            $breadcrumbService = app(BreadcrumbService::class);
            $breadcrumbs = $breadcrumbService->generate(request());
            $structuredData = $breadcrumbService->generateStructuredData($breadcrumbs);

            $view->with('breadcrumbs', $breadcrumbs);
            $view->with('breadcrumbStructuredData', $structuredData);
        });
    }
}
