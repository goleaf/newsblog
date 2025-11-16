<?php

namespace Tests\Feature;

use App\Jobs\CheckBrokenLinks;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrokenLinkCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_scans_links_and_marks_broken_and_ok(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
            'content' => '<p>See <a href="https://example.com/ok">ok</a> and <a href="https://example.com/missing">missing</a></p>',
        ]);

        Http::fake([
            'https://example.com/ok' => Http::response('', 200),
            'https://example.com/missing' => Http::response('', 404),
        ]);

        (new CheckBrokenLinks())->handle();

        $this->assertDatabaseHas('broken_links', [
            'post_id' => $post->id,
            'url' => 'https://example.com/ok',
            'status' => 'ok',
        ]);

        $this->assertDatabaseHas('broken_links', [
            'post_id' => $post->id,
            'url' => 'https://example.com/missing',
            'status' => 'broken',
            'response_code' => 404,
        ]);
    }

    public function test_it_ignores_internal_links(): void
    {
        config()->set('app.url', 'https://myapp.test');

        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
            'content' => '<a href="https://myapp.test/posts/1">internal</a> <a href="https://external.com">external</a>',
        ]);

        Http::fake([
            'https://external.com' => Http::response('', 200),
        ]);

        (new CheckBrokenLinks())->handle();

        $this->assertDatabaseMissing('broken_links', [
            'post_id' => $post->id,
            'url' => 'https://myapp.test/posts/1',
        ]);
        $this->assertDatabaseHas('broken_links', [
            'post_id' => $post->id,
            'url' => 'https://external.com',
            'status' => 'ok',
        ]);
    }
}


