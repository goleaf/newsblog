<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_tags(): void
    {
        Tag::factory()->count(3)->create();

        $res = $this->getJson('/api/v1/tags');
        $res->assertOk();
        $res->assertJsonStructure([
            'data' => [['id', 'name', 'slug', 'description', 'created_at']],
            'total',
            'links' => ['next', 'prev'],
        ]);
    }

    public function test_can_list_articles_for_tag(): void
    {
        $tag = Tag::factory()->create();

        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $post->tags()->attach($tag->id);

        $res = $this->getJson('/api/v1/tags/'.$tag->id.'/articles');
        $res->assertOk();
        $this->assertArrayHasKey('data', $res->json());
        $this->assertGreaterThanOrEqual(1, $res->json('total'));
    }
}
