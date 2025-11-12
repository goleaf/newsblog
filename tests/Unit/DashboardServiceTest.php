<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostView;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardService;
        Cache::flush();
    }

    public function test_get_metrics_returns_all_required_data(): void
    {
        $metrics = $this->service->getMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_posts', $metrics);
        $this->assertArrayHasKey('posts_comparison', $metrics);
        $this->assertArrayHasKey('views_today', $metrics);
        $this->assertArrayHasKey('views_week', $metrics);
        $this->assertArrayHasKey('views_month', $metrics);
        $this->assertArrayHasKey('pending_comments', $metrics);
        $this->assertArrayHasKey('top_posts', $metrics);
        $this->assertArrayHasKey('posts_chart_data', $metrics);
    }

    public function test_metrics_are_cached(): void
    {
        // First call should cache the metrics
        $metrics1 = $this->service->getMetrics();

        // Create a new post
        Post::factory()->create(['status' => 'published', 'published_at' => now()]);

        // Second call should return cached data (same count)
        $metrics2 = $this->service->getMetrics();

        $this->assertEquals($metrics1['total_posts'], $metrics2['total_posts']);

        // Clear cache and verify new data is fetched
        $this->service->clearCache();
        $metrics3 = $this->service->getMetrics();

        $this->assertEquals($metrics1['total_posts'] + 1, $metrics3['total_posts']);
    }

    public function test_total_posts_counts_only_published(): void
    {
        Post::factory()->count(5)->create(['status' => 'published', 'published_at' => now()]);
        Post::factory()->count(3)->create(['status' => 'draft']);

        $metrics = $this->service->getMetrics();

        $this->assertEquals(5, $metrics['total_posts']);
    }

    public function test_posts_comparison_calculates_percentage_change(): void
    {
        // Create posts in current period (last 30 days)
        Post::factory()->count(10)->create([
            'status' => 'published',
            'published_at' => now()->subDays(15),
        ]);

        // Create posts in previous period (30-60 days ago)
        Post::factory()->count(5)->create([
            'status' => 'published',
            'published_at' => now()->subDays(45),
        ]);

        $metrics = $this->service->getMetrics();
        $comparison = $metrics['posts_comparison'];

        $this->assertEquals(10, $comparison['current']);
        $this->assertEquals(5, $comparison['previous']);
        $this->assertEquals(100.0, $comparison['percentage']); // 100% increase
        $this->assertTrue($comparison['is_increase']);
    }

    public function test_views_today_counts_only_today(): void
    {
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

        PostView::factory()->count(5)->create([
            'post_id' => $post->id,
            'viewed_at' => now(),
        ]);

        PostView::factory()->count(3)->create([
            'post_id' => $post->id,
            'viewed_at' => now()->subDays(1),
        ]);

        $metrics = $this->service->getMetrics();

        $this->assertEquals(5, $metrics['views_today']);
    }

    public function test_views_week_counts_last_seven_days(): void
    {
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

        PostView::factory()->count(10)->create([
            'post_id' => $post->id,
            'viewed_at' => now()->subDays(3),
        ]);

        PostView::factory()->count(5)->create([
            'post_id' => $post->id,
            'viewed_at' => now()->subDays(10),
        ]);

        $metrics = $this->service->getMetrics();

        $this->assertEquals(10, $metrics['views_week']);
    }

    public function test_views_month_counts_last_thirty_days(): void
    {
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

        PostView::factory()->count(20)->create([
            'post_id' => $post->id,
            'viewed_at' => now()->subDays(15),
        ]);

        PostView::factory()->count(10)->create([
            'post_id' => $post->id,
            'viewed_at' => now()->subDays(40),
        ]);

        $metrics = $this->service->getMetrics();

        $this->assertEquals(20, $metrics['views_month']);
    }

    public function test_pending_comments_count(): void
    {
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

        Comment::factory()->count(7)->create([
            'post_id' => $post->id,
            'status' => 'pending',
        ]);

        Comment::factory()->count(3)->create([
            'post_id' => $post->id,
            'status' => 'approved',
        ]);

        $metrics = $this->service->getMetrics();

        $this->assertEquals(7, $metrics['pending_comments']);
    }

    public function test_top_posts_returns_ten_most_viewed(): void
    {
        // Create posts with different view counts
        for ($i = 1; $i <= 15; $i++) {
            Post::factory()->create([
                'status' => 'published',
                'published_at' => now(),
                'view_count' => $i * 10,
            ]);
        }

        $metrics = $this->service->getMetrics();
        $topPosts = $metrics['top_posts'];

        $this->assertCount(10, $topPosts);
        $this->assertEquals(150, $topPosts[0]['view_count']); // Highest view count
        $this->assertEquals(60, $topPosts[9]['view_count']); // 10th highest
    }

    public function test_posts_chart_data_covers_thirty_days(): void
    {
        $metrics = $this->service->getMetrics();
        $chartData = $metrics['posts_chart_data'];

        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
        $this->assertCount(30, $chartData['labels']);
        $this->assertCount(30, $chartData['data']);
    }

    public function test_posts_chart_data_includes_all_dates(): void
    {
        // Create posts on specific dates
        Post::factory()->count(3)->create([
            'status' => 'published',
            'published_at' => now()->subDays(5),
        ]);

        Post::factory()->count(2)->create([
            'status' => 'published',
            'published_at' => now()->subDays(10),
        ]);

        $metrics = $this->service->getMetrics();
        $chartData = $metrics['posts_chart_data'];

        // Verify that the data array has values for the dates with posts
        $this->assertContains(3, $chartData['data']);
        $this->assertContains(2, $chartData['data']);
        $this->assertContains(0, $chartData['data']); // Days with no posts
    }

    public function test_clear_cache_removes_cached_metrics(): void
    {
        // Cache metrics
        $this->service->getMetrics();
        $this->assertTrue(Cache::has('dashboard.metrics'));

        // Clear cache
        $this->service->clearCache();
        $this->assertFalse(Cache::has('dashboard.metrics'));
    }
}
