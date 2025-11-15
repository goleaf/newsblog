<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\MonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected MonitoringService $monitoring;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitoring = app(MonitoringService::class);
        Cache::flush();
    }

    public function test_tracks_dnt_compliance(): void
    {
        $this->monitoring->trackDntCompliance(true, 'post.show');
        $this->monitoring->trackDntCompliance(false, 'post.show');
        $this->monitoring->trackDntCompliance(true, 'post.show');

        $metrics = $this->monitoring->getMetricsSnapshot();

        $this->assertEquals(2, $metrics['dnt']['enabled']);
        $this->assertEquals(1, $metrics['dnt']['disabled']);
    }

    public function test_tracks_view_performance(): void
    {
        $post = Post::factory()->create();

        $this->monitoring->trackViewPerformance($post->id, 0.05, true);
        $this->monitoring->trackViewPerformance($post->id, 0.03, true);

        $metrics = $this->monitoring->getMetricsSnapshot();

        $this->assertEquals(2, $metrics['post_views']['total']);
        $this->assertEquals(2, $metrics['post_views']['queued']);
        $this->assertNotNull($metrics['post_views']['latest']);
    }

    public function test_tracks_engagement_metrics(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();

        $this->monitoring->trackEngagementMetric('scroll', $post->id, $user->id);
        $this->monitoring->trackEngagementMetric('time_spent', $post->id, null);

        $metrics = $this->monitoring->getMetricsSnapshot();

        $this->assertEquals(2, $metrics['engagement']['total']);
        $this->assertEquals(1, $metrics['engagement']['scroll']);
        $this->assertEquals(1, $metrics['engagement']['time_spent']);
        $this->assertEquals(1, $metrics['engagement']['authenticated']);
        $this->assertEquals(1, $metrics['engagement']['anonymous']);
    }

    public function test_tracks_search_performance(): void
    {
        $this->monitoring->trackSearchPerformance('laravel', 10, 0.15);
        $this->monitoring->trackSearchPerformance('nonexistent', 0, 0.08);

        $metrics = $this->monitoring->getMetricsSnapshot();

        $this->assertEquals(2, $metrics['search']['total']);
        $this->assertEquals(1, $metrics['search']['zero_results']);
        $this->assertNotNull($metrics['search']['latest']);
    }

    public function test_tracks_errors(): void
    {
        $this->monitoring->trackError('tracking', 'Test error message');
        $this->monitoring->trackError('database', 'Database connection failed');

        $metrics = $this->monitoring->getMetricsSnapshot();

        $this->assertEquals(2, $metrics['errors']['total']);
        $this->assertEquals(1, $metrics['errors']['tracking']);
        $this->assertEquals(1, $metrics['errors']['database']);
    }

    public function test_checks_alert_thresholds(): void
    {
        // Generate high error count
        for ($i = 0; $i < 150; $i++) {
            $this->monitoring->trackError('test', 'Test error');
        }

        $alerts = $this->monitoring->checkAlertThresholds();

        $this->assertNotEmpty($alerts);
        $this->assertEquals('high', $alerts[0]['severity']);
        $this->assertEquals('error_rate', $alerts[0]['type']);
    }

    public function test_checks_search_quality_alerts(): void
    {
        // Generate searches with high zero-result rate
        for ($i = 0; $i < 10; $i++) {
            $this->monitoring->trackSearchPerformance('query', 0, 0.1);
        }
        for ($i = 0; $i < 5; $i++) {
            $this->monitoring->trackSearchPerformance('query', 10, 0.1);
        }

        $alerts = $this->monitoring->checkAlertThresholds();

        $searchQualityAlert = collect($alerts)->firstWhere('type', 'search_quality');
        $this->assertNotNull($searchQualityAlert);
        $this->assertEquals('medium', $searchQualityAlert['severity']);
    }

    public function test_resets_metrics(): void
    {
        $this->monitoring->trackDntCompliance(true, 'post.show');
        $this->monitoring->trackError('test', 'Test error');

        $this->monitoring->resetMetrics();

        $metrics = $this->monitoring->getMetricsSnapshot();

        $this->assertEquals(0, $metrics['dnt']['enabled']);
        $this->assertEquals(0, $metrics['errors']['total']);
    }

    public function test_admin_can_access_monitoring_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.monitoring.index'));

        $response->assertOk();
        $response->assertViewIs('admin.monitoring.index');
        $response->assertViewHas('metrics');
        $response->assertViewHas('alerts');
    }

    public function test_non_admin_cannot_access_monitoring_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('admin.monitoring.index'));

        $response->assertForbidden();
    }

    public function test_dnt_header_prevents_tracking(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->withHeaders(['DNT' => '1'])
            ->get(route('post.show', $post->slug));

        $response->assertOk();

        // Verify DNT compliance was tracked
        $metrics = $this->monitoring->getMetricsSnapshot();
        $this->assertGreaterThan(0, $metrics['dnt']['enabled']);
    }

    public function test_engagement_tracking_respects_dnt(): void
    {
        $post = Post::factory()->create();

        $response = $this->withHeaders(['DNT' => '1'])
            ->postJson(route('engagement.track'), [
                'post_id' => $post->id,
                'scroll_depth' => 50,
            ]);

        $response->assertOk();
        $response->assertJson(['message' => 'Tracking disabled']);
    }
}
