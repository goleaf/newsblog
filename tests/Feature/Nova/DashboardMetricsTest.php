<?php

namespace Tests\Feature\Nova;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use App\Nova\Metrics\PostsByCategory;
use App\Nova\Metrics\PostsByStatus;
use App\Nova\Metrics\PostsPerDay;
use App\Nova\Metrics\TotalPosts;
use App\Nova\Metrics\TotalUsers;
use App\Nova\Metrics\TotalViews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_total_posts_metric_counts_published_posts(): void
    {
        Post::factory()->count(5)->create(['status' => 'published']);
        Post::factory()->count(3)->create(['status' => 'draft']);

        $metric = new TotalPosts;
        $request = NovaRequest::create('/nova-api/metrics/total-posts', 'GET', ['range' => 30]);
        $request->setUserResolver(fn () => $this->admin);

        $result = $metric->calculate($request);

        $this->assertEquals(5, $result->value);
    }

    public function test_total_users_metric_counts_active_users(): void
    {
        User::factory()->count(10)->create(['status' => 'active']);
        User::factory()->count(2)->create(['status' => 'inactive']);
        User::factory()->count(1)->create(['status' => 'suspended']);

        $metric = new TotalUsers;
        $request = NovaRequest::create('/nova-api/metrics/total-users', 'GET', ['range' => 30]);
        $request->setUserResolver(fn () => $this->admin);

        $result = $metric->calculate($request);

        $this->assertEquals(11, $result->value); // 10 + 1 admin
    }

    public function test_total_views_metric_counts_views_this_month(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        PostView::factory()->count(15)->create([
            'post_id' => $post->id,
            'viewed_at' => now(),
        ]);

        PostView::factory()->count(5)->create([
            'post_id' => $post->id,
            'viewed_at' => now()->subMonths(2),
        ]);

        $metric = new TotalViews;
        $request = NovaRequest::create('/nova-api/metrics/total-views', 'GET', ['range' => 30]);
        $request->setUserResolver(fn () => $this->admin);

        $result = $metric->calculate($request);

        $this->assertEquals(15, $result->value);
    }

    public function test_posts_per_day_metric_returns_trend_data(): void
    {
        Post::factory()->count(3)->create([
            'status' => 'published',
            'published_at' => now()->subDays(5),
        ]);

        Post::factory()->count(2)->create([
            'status' => 'published',
            'published_at' => now()->subDays(10),
        ]);

        $metric = new PostsPerDay;
        $request = NovaRequest::create('/nova-api/metrics/posts-per-day', 'GET', ['range' => 30]);
        $request->setUserResolver(fn () => $this->admin);

        $result = $metric->calculate($request);

        $this->assertIsArray($result->trend);
        $this->assertNotEmpty($result->trend);
    }

    public function test_posts_by_status_metric_partitions_by_status(): void
    {
        Post::factory()->count(5)->create(['status' => 'published']);
        Post::factory()->count(3)->create(['status' => 'draft']);
        Post::factory()->count(2)->create(['status' => 'scheduled']);

        $metric = new PostsByStatus;
        $request = NovaRequest::create('/nova-api/metrics/posts-by-status', 'GET');
        $request->setUserResolver(fn () => $this->admin);

        $result = $metric->calculate($request);

        $this->assertIsArray($result->value);
        $this->assertNotEmpty($result->value);

        // Check that we have the expected statuses (keys might be labeled or raw)
        $totalCount = array_sum($result->value);
        $this->assertEquals(10, $totalCount);
    }

    public function test_posts_by_category_metric_partitions_by_category(): void
    {
        $category1 = Category::factory()->create(['name' => 'Technology']);
        $category2 = Category::factory()->create(['name' => 'Science']);

        Post::factory()->count(7)->for($category1)->create();
        Post::factory()->count(4)->for($category2)->create();

        $metric = new PostsByCategory;
        $request = NovaRequest::create('/nova-api/metrics/posts-by-category', 'GET');
        $request->setUserResolver(fn () => $this->admin);

        $result = $metric->calculate($request);

        $this->assertArrayHasKey('Technology', $result->value);
        $this->assertArrayHasKey('Science', $result->value);
        $this->assertEquals(7, $result->value['Technology']);
        $this->assertEquals(4, $result->value['Science']);
    }

    public function test_metrics_have_caching_configured(): void
    {
        $metrics = [
            new TotalPosts,
            new TotalUsers,
            new TotalViews,
            new PostsPerDay,
            new PostsByStatus,
            new PostsByCategory,
        ];

        foreach ($metrics as $metric) {
            $cacheFor = $metric->cacheFor();
            $this->assertNotNull($cacheFor, get_class($metric).' should have caching configured');
            $this->assertGreaterThan(4, now()->diffInMinutes($cacheFor), get_class($metric).' should cache for at least 5 minutes');
        }
    }

    public function test_main_dashboard_has_all_metrics(): void
    {
        $dashboard = new \App\Nova\Dashboards\Main;
        $cards = $dashboard->cards();

        $this->assertCount(6, $cards, 'Main dashboard should have 6 metrics');

        $metricClasses = array_map(fn ($card) => get_class($card), $cards);

        $this->assertContains(TotalPosts::class, $metricClasses);
        $this->assertContains(TotalUsers::class, $metricClasses);
        $this->assertContains(TotalViews::class, $metricClasses);
        $this->assertContains(PostsPerDay::class, $metricClasses);
        $this->assertContains(PostsByStatus::class, $metricClasses);
        $this->assertContains(PostsByCategory::class, $metricClasses);
    }

    public function test_main_dashboard_has_proper_configuration(): void
    {
        $dashboard = new \App\Nova\Dashboards\Main;

        $this->assertEquals('Tech News Dashboard', $dashboard->name());
        $this->assertEquals('main', $dashboard->uriKey());
    }
}
