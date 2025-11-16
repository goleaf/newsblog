<?php

namespace Tests\Feature\Admin;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_content_calendar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.calendar.index'));

        $response->assertOk();
        $response->assertViewIs('admin.calendar.index');
        $response->assertSee(__('Content Calendar'));
    }

    public function test_get_posts_for_date_requires_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->getJson(route('admin.calendar.posts'));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date']);
    }

    public function test_update_post_date_updates_scheduled_at_for_scheduled_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(2)->setTime(10, 15),
            'published_at' => null,
        ]);

        $newDate = now()->addDays(5)->toDateString();

        $response = $this->actingAs($admin)->postJson(
            route('admin.calendar.posts.update-date', $post),
            ['date' => $newDate]
        );

        $response->assertOk()->assertJsonFragment(['success' => true]);
        $post->refresh();
        $this->assertNotNull($post->scheduled_at);
        $this->assertEquals($newDate, $post->scheduled_at->toDateString());
        // Keeps original time hour/minute
        $this->assertEquals(10, (int) $post->scheduled_at->format('H'));
        $this->assertEquals(15, (int) $post->scheduled_at->format('i'));
    }

    public function test_update_post_date_updates_published_at_for_published_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay()->setTime(8, 30),
            'scheduled_at' => null,
        ]);

        $newDate = now()->addDays(1)->toDateString();

        $response = $this->actingAs($admin)->postJson(
            route('admin.calendar.posts.update-date', $post),
            ['date' => $newDate]
        );

        $response->assertOk()->assertJsonFragment(['success' => true]);
        $post->refresh();
        $this->assertNotNull($post->published_at);
        $this->assertEquals($newDate, $post->published_at->toDateString());
        $this->assertEquals(8, (int) $post->published_at->format('H'));
        $this->assertEquals(30, (int) $post->published_at->format('i'));
    }

    public function test_update_post_date_schedules_draft_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create([
            'status' => 'draft',
            'published_at' => null,
            'scheduled_at' => null,
        ]);

        $newDate = now()->addDays(3)->toDateString();

        $response = $this->actingAs($admin)->postJson(
            route('admin.calendar.posts.update-date', $post),
            ['date' => $newDate]
        );

        $response->assertOk()->assertJsonFragment(['success' => true]);
        $post->refresh();
        $this->assertEquals('scheduled', $post->status->value);
        $this->assertNotNull($post->scheduled_at);
        $this->assertEquals($newDate, $post->scheduled_at->toDateString());
        // Defaults to 09:00 time for drafts
        $this->assertEquals(9, (int) $post->scheduled_at->format('H'));
        $this->assertEquals(0, (int) $post->scheduled_at->format('i'));
    }
}


