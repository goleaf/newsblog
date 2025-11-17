<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test view tracking respects Do Not Track header
     * Requirement: 16.1, 16.4
     */
    public function test_view_tracking_respects_do_not_track(): void
    {
        $post = Post::factory()->create();

        // Request with DNT header
        $response = $this->withHeaders(['DNT' => '1'])
            ->get(route('post.show', $post->slug));

        $response->assertOk();

        // View should not be tracked
        $this->assertDatabaseMissing('post_views', [
            'post_id' => $post->id,
        ]);
    }

    /**
     * Test view tracking stores metadata
     * Requirement: 16.1
     */
    public function test_view_tracking_stores_metadata(): void
    {
        $post = Post::factory()->create();

        $response = $this->withHeaders([
            'User-Agent' => 'Test Browser',
            'Referer' => 'https://example.com',
        ])->get(route('post.show', $post->slug));

        $response->assertOk();

        // Wait for job to process
        $this->artisan('queue:work --once --stop-when-empty');

        $this->assertDatabaseHas('post_views', [
            'post_id' => $post->id,
            'user_agent' => 'Test Browser',
            'referer' => 'https://example.com',
        ]);
    }

    /**
     * Test search click tracking
     * Requirement: 16.2
     */
    public function test_search_click_tracking(): void
    {
        $post = Post::factory()->create();
        $searchLog = SearchLog::factory()->create([
            'query' => 'test query',
        ]);

        $response = $this->postJson(route('search.track-click'), [
            'search_log_id' => $searchLog->id,
            'post_id' => $post->id,
            'position' => 0,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('search_clicks', [
            'search_log_id' => $searchLog->id,
            'post_id' => $post->id,
            'position' => 0,
        ]);
    }

    /**
     * Test engagement metrics tracking
     * Requirement: 16.3
     */
    public function test_engagement_metrics_tracking(): void
    {
        $post = Post::factory()->create();

        $response = $this->postJson(route('engagement.track'), [
            'post_id' => $post->id,
            'time_on_page' => 120,
            'scroll_depth' => 75,
            'clicked_bookmark' => true,
            'clicked_share' => false,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('engagement_metrics', [
            'post_id' => $post->id,
            'time_on_page' => 120,
            'scroll_depth' => 75,
            'clicked_bookmark' => true,
            'clicked_share' => false,
        ]);
    }

    /**
     * Test engagement tracking respects Do Not Track
     * Requirement: 16.4
     */
    public function test_engagement_tracking_respects_do_not_track(): void
    {
        $post = Post::factory()->create();

        $response = $this->withHeaders(['DNT' => '1'])
            ->postJson(route('engagement.track'), [
                'post_id' => $post->id,
                'time_on_page' => 120,
            ]);

        $response->assertOk();

        // Metric should not be tracked
        $this->assertDatabaseMissing('engagement_metrics', [
            'post_id' => $post->id,
        ]);
    }

    /**
     * Test GDPR cookie consent acceptance
     * Requirement: 16.4
     */
    public function test_gdpr_cookie_consent_acceptance(): void
    {
        $response = $this->postJson(route('gdpr.accept-consent'));

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertCookie('gdpr_consent', 'accepted');
    }

    /**
     * Test GDPR cookie consent decline
     * Requirement: 16.4
     */
    public function test_gdpr_cookie_consent_decline(): void
    {
        $response = $this->postJson(route('gdpr.decline-consent'));

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertCookie('gdpr_consent', 'declined');
    }

    /**
     * Test GDPR data export
     * Requirement: 16.4
     */
    public function test_gdpr_data_export(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('gdpr.export-data'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([
                'user',
                'posts',
                'comments',
                'bookmarks',
                'reactions',
                'media',
            ]);
    }

    /**
     * Test analytics dashboard access
     * Requirement: 16.3
     */
    public function test_analytics_dashboard_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.analytics'));

        $response->assertOk()
            ->assertViewIs('admin.analytics.index')
            ->assertViewHas([
                'viewStats',
                'engagementStats',
                'searchStats',
                'topQueries',
                'topPosts',
            ]);
    }

    /**
     * Test analytics dashboard requires authentication
     * Requirement: 16.3
     */
    public function test_analytics_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('admin.analytics'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test engagement metrics update existing records
     * Requirement: 16.3
     */
    public function test_engagement_metrics_update_existing_records(): void
    {
        $post = Post::factory()->create();
        $sessionId = session()->getId();

        // First tracking
        $this->postJson(route('engagement.track'), [
            'post_id' => $post->id,
            'time_on_page' => 60,
            'scroll_depth' => 50,
        ]);

        // Second tracking with higher values
        $this->postJson(route('engagement.track'), [
            'post_id' => $post->id,
            'time_on_page' => 120,
            'scroll_depth' => 75,
        ]);

        // Should have only one record with updated values
        $this->assertDatabaseCount('engagement_metrics', 1);
        // Session IDs can vary under the array driver across requests; assert core fields
        $this->assertDatabaseHas('engagement_metrics', [
            'post_id' => $post->id,
            'time_on_page' => 120,
            'scroll_depth' => 75,
        ]);
    }
}
