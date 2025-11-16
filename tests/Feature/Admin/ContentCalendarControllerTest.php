<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentCalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure a minimal Vite manifest exists for page-specific assets used by the view
        $buildDir = public_path('build');
        if (! is_dir($buildDir)) {
            mkdir($buildDir, 0777, true);
        }

        $manifestPath = $buildDir.'/manifest.json';
        $manifest = file_exists($manifestPath)
            ? json_decode(file_get_contents($manifestPath), true) ?: []
            : [];

        // Core app entry to satisfy dynamic imports when present
        $manifest['resources/js/app.js'] = $manifest['resources/js/app.js'] ?? [
            'file' => 'assets/app.js',
            'src' => 'resources/js/app.js',
            'isEntry' => true,
        ];

        // Admin calendar page chunk referenced by the blade via @vite
        $manifest['resources/js/pages/admin-calendar.js'] = [
            'file' => 'assets/admin-calendar.js',
            'src' => 'resources/js/pages/admin-calendar.js',
            'isEntry' => true,
        ];

        file_put_contents($manifestPath, json_encode($manifest));
    }

    public function test_admin_can_access_content_calendar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.calendar.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.calendar.index');
        $response->assertViewHas(['date', 'posts']);
    }

    public function test_editor_can_access_content_calendar(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $response = $this->actingAs($editor)->get(route('admin.calendar.index'));

        $response->assertStatus(200);
    }

    public function test_author_cannot_access_content_calendar(): void
    {
        $author = User::factory()->create(['role' => 'author']);

        $response = $this->actingAs($author)->get(route('admin.calendar.index'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_content_calendar(): void
    {
        $response = $this->get(route('admin.calendar.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_calendar_displays_posts_for_current_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        // Create posts for current month
        $publishedPost = Post::factory()->create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $scheduledPost = Post::factory()->create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.calendar.index'));

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertNotEmpty($posts);
    }

    public function test_can_get_posts_for_specific_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $date = now()->format('Y-m-d');

        $post = Post::factory()->create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => $date,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.calendar.posts', ['date' => $date]));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['title' => $post->title]);
    }

    public function test_can_update_post_date_via_drag_and_drop(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'scheduled',
            'scheduled_at' => now(),
        ]);

        $newDate = now()->addDays(7)->format('Y-m-d');

        $response = $this->actingAs($admin)->postJson(
            route('admin.calendar.posts.update-date', $post),
            ['date' => $newDate]
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $post->refresh();
        $this->assertEquals($newDate, $post->scheduled_at->format('Y-m-d'));
    }

    public function test_updating_published_post_date_updates_published_at(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $newDate = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($admin)->postJson(
            route('admin.calendar.posts.update-date', $post),
            ['date' => $newDate]
        );

        $response->assertStatus(200);

        $post->refresh();
        $this->assertEquals($newDate, $post->published_at->format('Y-m-d'));
    }

    public function test_calendar_navigation_works_with_month_and_year_parameters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.calendar.index', [
            'month' => 12,
            'year' => 2024,
        ]));

        $response->assertStatus(200);
        $date = $response->viewData('date');
        $this->assertEquals(12, $date->month);
        $this->assertEquals(2024, $date->year);
    }
}
