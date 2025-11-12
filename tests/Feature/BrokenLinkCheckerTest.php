<?php

namespace Tests\Feature;

use App\Jobs\CheckBrokenLinks;
use App\Models\BrokenLink;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrokenLinkCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_detects_broken_links_with_404_status(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
            'content' => '<p>Check out <a href="https://example.com/broken">this link</a></p>',
        ]);

        Http::fake([
            'example.com/broken' => Http::response('Not Found', 404),
        ]);

        $job = new CheckBrokenLinks;
        $job->handle();

        $this->assertDatabaseHas('broken_links', [
            'post_id' => $post->id,
            'url' => 'https://example.com/broken',
            'status_code' => 404,
            'status' => 'pending',
        ]);
    }

    public function test_job_detects_broken_links_with_timeout(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
            'content' => '<p>Check out <a href="https://timeout.example.com">this link</a></p>',
        ]);

        Http::fake([
            'timeout.example.com' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);

        $job = new CheckBrokenLinks;
        $job->handle();

        $this->assertDatabaseHas('broken_links', [
            'post_id' => $post->id,
            'url' => 'https://timeout.example.com',
            'status' => 'pending',
        ]);

        $brokenLink = BrokenLink::where('post_id', $post->id)->first();
        $this->assertNotNull($brokenLink->error_message);
    }

    public function test_job_removes_fixed_links(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
            'content' => '<p>Check out <a href="https://example.com/working">this link</a></p>',
        ]);

        BrokenLink::create([
            'post_id' => $post->id,
            'url' => 'https://example.com/working',
            'status_code' => 404,
            'last_checked_at' => now(),
            'status' => 'pending',
        ]);

        Http::fake([
            'example.com/working' => Http::response('OK', 200),
        ]);

        $job = new CheckBrokenLinks;
        $job->handle();

        $this->assertDatabaseMissing('broken_links', [
            'post_id' => $post->id,
            'url' => 'https://example.com/working',
            'status' => 'pending',
        ]);
    }

    public function test_job_skips_internal_links(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
            'content' => '<p>Check out <a href="/internal-page">this link</a> and <a href="mailto:test@example.com">email</a></p>',
        ]);

        $job = new CheckBrokenLinks;
        $job->handle();

        $this->assertDatabaseCount('broken_links', 0);
    }

    public function test_admin_can_view_broken_links_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create([
            'user_id' => $admin->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        BrokenLink::create([
            'post_id' => $post->id,
            'url' => 'https://example.com/broken',
            'status_code' => 404,
            'last_checked_at' => now(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.broken-links.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.broken-links.index');
        $response->assertViewHas('brokenLinks');
        $response->assertViewHas('stats');
    }

    public function test_admin_can_mark_link_as_fixed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create([
            'user_id' => $admin->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $brokenLink = BrokenLink::create([
            'post_id' => $post->id,
            'url' => 'https://example.com/broken',
            'status_code' => 404,
            'last_checked_at' => now(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.broken-links.mark-fixed', $brokenLink));

        $response->assertRedirect();
        $this->assertDatabaseHas('broken_links', [
            'id' => $brokenLink->id,
            'status' => 'fixed',
        ]);
    }

    public function test_admin_can_mark_link_as_ignored(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create([
            'user_id' => $admin->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $brokenLink = BrokenLink::create([
            'post_id' => $post->id,
            'url' => 'https://example.com/broken',
            'status_code' => 404,
            'last_checked_at' => now(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.broken-links.mark-ignored', $brokenLink));

        $response->assertRedirect();
        $this->assertDatabaseHas('broken_links', [
            'id' => $brokenLink->id,
            'status' => 'ignored',
        ]);
    }

    public function test_admin_can_delete_broken_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create([
            'user_id' => $admin->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $brokenLink = BrokenLink::create([
            'post_id' => $post->id,
            'url' => 'https://example.com/broken',
            'status_code' => 404,
            'last_checked_at' => now(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.broken-links.destroy', $brokenLink));

        $response->assertRedirect();
        $this->assertDatabaseMissing('broken_links', [
            'id' => $brokenLink->id,
        ]);
    }

    public function test_admin_can_perform_bulk_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create([
            'user_id' => $admin->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $link1 = BrokenLink::create([
            'post_id' => $post->id,
            'url' => 'https://example.com/broken1',
            'status_code' => 404,
            'last_checked_at' => now(),
            'status' => 'pending',
        ]);

        $link2 = BrokenLink::create([
            'post_id' => $post->id,
            'url' => 'https://example.com/broken2',
            'status_code' => 404,
            'last_checked_at' => now(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.broken-links.bulk-action'), [
                'action' => 'fix',
                'ids' => [$link1->id, $link2->id],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('broken_links', [
            'id' => $link1->id,
            'status' => 'fixed',
        ]);
        $this->assertDatabaseHas('broken_links', [
            'id' => $link2->id,
            'status' => 'fixed',
        ]);
    }

    public function test_non_admin_cannot_access_broken_links_report(): void
    {
        $user = User::factory()->create(['role' => 'author']);

        $response = $this->actingAs($user)->get(route('admin.broken-links.index'));

        $response->assertStatus(403);
    }
}
