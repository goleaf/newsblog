<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_list_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        
        Post::factory()->count(5)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'slug', 'excerpt']
                ]
            ]);
    }

    public function test_api_show_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson("/api/v1/posts/{$post->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'slug', 'content', 'author', 'category']
            ]);
    }

    public function test_api_rate_limiting(): void
    {
        for ($i = 0; $i < 101; $i++) {
            $response = $this->getJson('/api/v1/posts');
            if ($i === 100) {
                $response->assertStatus(429);
            }
        }
    }
}

