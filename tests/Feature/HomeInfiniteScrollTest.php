<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeInfiniteScrollTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    public function test_home_returns_json_for_ajax_requests(): void
    {
        Post::factory()->count(24)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson(route('home') . '?page=2', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'html',
                'currentPage',
                'lastPage',
                'hasMorePages',
            ])
            ->assertJson([
                'currentPage' => 2,
            ]);

        $this->assertStringContainsString('data-post-item', $response->json('html'));
    }

    public function test_home_returns_html_for_regular_requests(): void
    {
        Post::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('home'));

        $response->assertStatus(200)
            ->assertViewIs('home')
            ->assertSee('x-data="infiniteScroll()"', false);
    }
}


