<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialShareApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_track_social_share_event(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $res = $this->postJson('/api/v1/shares', [
            'post_id' => $post->id,
            'provider' => 'twitter',
            'share_url' => 'https://twitter.com/intent/tweet?text=test',
        ]);

        $res->assertCreated();
        $res->assertJson([
            'post_id' => $post->id,
            'provider' => 'twitter',
        ]);
        $this->assertDatabaseHas('social_shares', [
            'post_id' => $post->id,
            'provider' => 'twitter',
        ]);
    }

    public function test_rejects_invalid_provider(): void
    {
        $post = Post::factory()->create();

        $res = $this->postJson('/api/v1/shares', [
            'post_id' => $post->id,
            'provider' => 'instagram',
        ]);

        $res->assertStatus(422);
    }
}
