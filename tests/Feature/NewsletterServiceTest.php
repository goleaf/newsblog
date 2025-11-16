<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Services\NewsletterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_digest_includes_top_posts(): void
    {
        $postA = Post::factory()->create([
            'title' => 'A Top Post',
            'status' => 'published',
            'view_count' => 500,
            'published_at' => now()->subHours(3),
        ]);
        $postB = Post::factory()->create([
            'title' => 'Second Post',
            'status' => 'published',
            'view_count' => 100,
            'published_at' => now()->subHours(2),
        ]);

        $html = app(NewsletterService::class)->generateDigest('daily', 2);

        $this->assertStringContainsString('A Top Post', $html);
        $this->assertStringContainsString('Second Post', $html);
    }
}
