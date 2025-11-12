<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
    }

    public function test_admin_sees_dashboard_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create some test data
        Post::factory()->count(5)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('metrics');

        $metrics = $response->viewData('metrics');
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_posts', $metrics);
        $this->assertArrayHasKey('views_today', $metrics);
        $this->assertArrayHasKey('pending_comments', $metrics);
    }

    public function test_editor_sees_dashboard_metrics(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($editor)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('metrics');
    }

    public function test_regular_user_does_not_see_metrics(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('metrics', null);
        $response->assertDontSee('Total Posts');
    }

    public function test_dashboard_displays_correct_post_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Post::factory()->count(10)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->count(5)->create(['status' => 'draft']);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $metrics = $response->viewData('metrics');
        $this->assertEquals(10, $metrics['total_posts']);
    }

    public function test_dashboard_displays_pending_comments_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        Comment::factory()->count(7)->create([
            'post_id' => $post->id,
            'status' => 'pending',
        ]);

        Comment::factory()->count(3)->create([
            'post_id' => $post->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('7'); // Pending comments count
    }

    public function test_dashboard_displays_top_posts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $topPost = Post::factory()->create([
            'title' => 'Most Popular Post',
            'status' => 'published',
            'published_at' => now(),
            'view_count' => 1000,
        ]);

        Post::factory()->count(5)->create([
            'status' => 'published',
            'published_at' => now(),
            'view_count' => 100,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $metrics = $response->viewData('metrics');
        $this->assertEquals('Most Popular Post', $metrics['top_posts'][0]['title']);
        $this->assertEquals(1000, $metrics['top_posts'][0]['view_count']);
    }

    public function test_dashboard_includes_chart_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Post::factory()->count(3)->create([
            'status' => 'published',
            'published_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $metrics = $response->viewData('metrics');
        $this->assertArrayHasKey('posts_chart_data', $metrics);
        $this->assertCount(30, $metrics['posts_chart_data']['labels']);
        $this->assertCount(30, $metrics['posts_chart_data']['data']);
    }

    public function test_dashboard_shows_percentage_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Current period posts
        Post::factory()->count(10)->create([
            'status' => 'published',
            'published_at' => now()->subDays(15),
        ]);

        // Previous period posts
        Post::factory()->count(5)->create([
            'status' => 'published',
            'published_at' => now()->subDays(45),
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $metrics = $response->viewData('metrics');
        $this->assertEquals(100.0, $metrics['posts_comparison']['percentage']);
        $this->assertTrue($metrics['posts_comparison']['is_increase']);
    }
}
