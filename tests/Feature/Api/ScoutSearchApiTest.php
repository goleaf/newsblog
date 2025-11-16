<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoutSearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_scout_engine_returns_results(): void
    {
        Post::factory()->published()->create(['title' => 'Laravel Scout Basics']);
        Post::factory()->published()->create(['title' => 'Advanced Laravel Tips']);
        Post::factory()->draft()->create(['title' => 'Unpublished secret']);

        $res = $this->getJson('/api/v1/search?q=Laravel&engine=scout');
        $res->assertOk()->assertJsonStructure([
            'success',
            'data',
            'meta' => ['query', 'count', 'fuzzy_enabled'],
        ]);

        $data = $res->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));

        // Ensure published posts are returned and drafts are excluded by shouldBeSearchable
        $titles = array_map(fn ($i) => $i['title'], $data);
        $this->assertContains('Laravel Scout Basics', $titles);
        $this->assertNotContains('Unpublished secret', $titles);
    }

    public function test_scout_engine_pagination_and_sorting(): void
    {
        // Create posts with different popularity and dates
        $p1 = Post::factory()->published()->create(['title' => 'A', 'view_count' => 10, 'published_at' => now()->subDays(3)]);
        $p2 = Post::factory()->published()->create(['title' => 'B', 'view_count' => 30, 'published_at' => now()->subDays(2)]);
        $p3 = Post::factory()->published()->create(['title' => 'C', 'view_count' => 20, 'published_at' => now()->subDay()]);

        // Sort by popularity (descending)
        $res = $this->getJson('/api/v1/search?q=a&engine=scout&sort=popularity&per_page=2&page=1');
        $res->assertOk();
        $data = $res->json('data');
        $this->assertCount(2, $data);

        // B should be before C before A when sorting by popularity
        $titles = array_map(fn ($i) => $i['title'], $data);
        $this->assertEquals('B', $titles[0]);

        // Page 2 should contain the remaining item
        $res2 = $this->getJson('/api/v1/search?q=a&engine=scout&sort=popularity&per_page=2&page=2');
        $res2->assertOk();
        $data2 = $res2->json('data');
        $this->assertCount(1, $data2);
    }
}
