<?php

namespace Tests\Feature\Ui;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class SponsoredLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_card_shows_sponsored_badge(): void
    {
        $post = Post::factory()->create([
            'is_sponsored' => true,
            'status' => PostStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);

        $html = View::make('components.content.post-card', ['post' => $post])->render();
        $this->assertStringContainsString('Sponsored', $html);
    }

    public function test_article_page_shows_sponsored_badge(): void
    {
        $post = Post::factory()->create([
            'is_sponsored' => true,
            'status' => PostStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('post.show', $post->slug));
        $response->assertOk();
        $response->assertSee('Sponsored', false);
    }
}
