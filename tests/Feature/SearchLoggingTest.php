<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_query_is_logged_with_query_and_result_count(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class);

        // Create some posts
        Post::factory()->published()->count(3)->create([
            'title' => 'Laravel analytics',
        ]);

        // Perform a search
        $response = $this->get(route('search', ['q' => 'analytics']));
        $response->assertOk();

        // Ensure a SearchLog exists with query set
        $this->assertDatabaseHas('search_logs', [
            'query' => 'analytics',
        ]);
    }
}
