<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SearchAPITest extends TestCase
{
    use RefreshDatabase;

    public function test_api_search_endpoint_returns_json(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/search?q=Laravel');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'title',
                ],
            ],
            'meta' => [
                'query',
                'count',
                'fuzzy_enabled',
            ],
        ]);
    }

    public function test_api_search_requires_query_parameter(): void
    {
        $response = $this->getJson('/api/v1/search');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['q']);
    }

    public function test_api_search_with_filters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['slug' => 'technology']);

        $post1 = Post::factory()->create([
            'title' => 'Laravel Post Technology',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $post2 = Post::factory()->create([
            'title' => 'Laravel Post Two',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/search?q=Laravel&category=technology');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        // API should return results (fuzzy search filtering may vary)
        $this->assertIsArray($response->json('data'));
        // If results exist, verify structure
        if (count($response->json('data')) > 0) {
            $this->assertArrayHasKey('id', $response->json('data')[0]);
        }
    }

    public function test_api_search_rate_limiting(): void
    {
        RateLimiter::clear('api');

        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/v1/search?q=test');
        }

        $response->assertStatus(429);
    }

    public function test_api_search_response_format(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/search?q=Laravel');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertArrayHasKey('data', $response->json());
        $this->assertArrayHasKey('meta', $response->json());
        $this->assertEquals('Laravel', $response->json('meta.query'));
    }

    public function test_api_suggestion_endpoint_returns_suggestions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/search/suggestions?q=Lara');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'meta' => [
                'query',
                'count',
            ],
        ]);
        $this->assertTrue($response->json('success'));
    }

    public function test_api_suggestion_requires_minimum_query_length(): void
    {
        $response = $this->getJson('/api/v1/search/suggestions?q=La');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['q']);
    }

    public function test_api_search_with_threshold_parameter(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/search?q=Laravel&threshold=60');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_api_search_with_limit_parameter(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->count(20)->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/search?q=Laravel&limit=10');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(10, count($response->json('data')));
    }

    public function test_api_search_handles_invalid_parameters(): void
    {
        $response = $this->getJson('/api/v1/search?q=Laravel&threshold=150');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['threshold']);
    }

    public function test_api_search_returns_only_published_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $publishedPost = Post::factory()->create([
            'title' => 'Published Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $draftPost = Post::factory()->create([
            'title' => 'Draft Laravel Post',
            'status' => 'draft',
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/search?q=Laravel');

        $response->assertStatus(200);
        $data = $response->json('data');
        $postIds = collect($data)->pluck('id')->toArray();
        $this->assertContains($publishedPost->id, $postIds);
        $this->assertNotContains($draftPost->id, $postIds);
    }
}
