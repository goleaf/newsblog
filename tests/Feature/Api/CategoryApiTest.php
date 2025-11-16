<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_categories(): void
    {
        Category::factory()->count(3)->create(['status' => 'active']);

        $res = $this->getJson('/api/v1/categories');
        $res->assertOk();
        $this->assertArrayHasKey('data', $res->json());
        $this->assertGreaterThanOrEqual(1, $res->json('total'));
    }

    public function test_can_list_articles_for_category(): void
    {
        $category = Category::factory()->create(['status' => 'active']);
        Post::factory()->count(2)->create([
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $res = $this->getJson("/api/v1/categories/{$category->id}/articles");
        $res->assertOk();
        $this->assertArrayHasKey('data', $res->json());
        $this->assertGreaterThanOrEqual(1, $res->json('total'));
    }
}
