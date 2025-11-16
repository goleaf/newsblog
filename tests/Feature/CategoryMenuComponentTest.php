<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CategoryMenuComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_menu_displays_active_categories(): void
    {
        $category = Category::factory()->create([
            'name' => 'Web Development',
            'status' => 'active',
            'parent_id' => null,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Web Development');
    }

    public function test_category_menu_displays_post_counts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'name' => 'Programming',
            'status' => 'active',
            'parent_id' => null,
        ]);

        Post::factory()->count(5)->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Programming');
        $response->assertSee('(5)');
    }

    public function test_category_menu_caches_results(): void
    {
        $category = Category::factory()->create([
            'name' => 'Cached Category',
            'status' => 'active',
            'parent_id' => null,
        ]);

        Cache::flush();

        // First request should cache
        $this->get('/');
        $this->assertTrue(Cache::has('category_menu'));

        // Second request should use cache
        $this->get('/');
        $this->assertTrue(Cache::has('category_menu'));
    }

    public function test_category_menu_only_shows_active_categories(): void
    {
        Category::factory()->create([
            'name' => 'Active Category',
            'status' => 'active',
            'parent_id' => null,
        ]);

        Category::factory()->create([
            'name' => 'Inactive Category',
            'status' => 'inactive',
            'parent_id' => null,
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Active Category');
        $response->assertDontSee('Inactive Category');
    }

    public function test_category_menu_has_proper_accessibility_attributes(): void
    {
        $category = Category::factory()->create([
            'name' => 'Accessible Category',
            'status' => 'active',
            'parent_id' => null,
        ]);

        Cache::flush();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('aria-label="Category navigation"', false);
    }
}
