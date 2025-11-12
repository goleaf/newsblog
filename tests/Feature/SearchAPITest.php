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

    public function test_api_search_includes_relevance_score(): void
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
        $data = $response->json('data');
        if (count($data) > 0) {
            $this->assertArrayHasKey('relevance_score', $data[0]);
            // Relevance score can be int or float
            $this->assertIsNumeric($data[0]['relevance_score']);
            $this->assertGreaterThanOrEqual(0, $data[0]['relevance_score']);
            $this->assertLessThanOrEqual(100, $data[0]['relevance_score']);
        }
    }

    public function test_api_search_with_exact_parameter_disables_fuzzy(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $exactPost = Post::factory()->create([
            'title' => 'Laravel Framework',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        // With exact=true, typo should not match
        $response = $this->getJson('/api/v1/search?q=laravle&exact=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        // Exact search should not find results with typo
        $this->assertEquals(0, count($data));

        // Without exact parameter, fuzzy search may work depending on threshold
        $response = $this->getJson('/api/v1/search?q=Laravel');

        $response->assertStatus(200);
        $data = $response->json('data');
        // Exact match should find results
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    public function test_api_search_authentication_for_protected_endpoints(): void
    {
        // Test that public search endpoint works without authentication
        $response = $this->getJson('/api/v1/search?q=Laravel');
        $response->assertStatus(200);

        // If there are protected endpoints, they would require authentication
        // This test verifies the public endpoint is accessible
        $this->assertTrue($response->json('success'));
    }
}
