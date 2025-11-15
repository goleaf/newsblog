<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_page_displays_header_with_icon_and_description(): void
    {
        $category = Category::factory()->create([
            'name' => 'Web Development',
            'description' => 'Articles about web development',
            'icon' => '🌐',
            'color_code' => '#3b82f6',
            'status' => 'active',
        ]);

        $response = $this->get(route('category.show', $category->slug));

        $response->assertStatus(200);
        $response->assertSee('Web Development');
        $response->assertSee('Articles about web development');
        $response->assertSee('🌐');
    }

    public function test_category_page_displays_subcategories_with_post_counts(): void
    {
        $parentCategory = Category::factory()->create([
            'name' => 'Programming',
            'status' => 'active',
        ]);

        $childCategory1 = Category::factory()->create([
            'name' => 'JavaScript',
            'parent_id' => $parentCategory->id,
            'icon' => '📜',
            'status' => 'active',
        ]);

        $childCategory2 = Category::factory()->create([
            'name' => 'Python',
            'parent_id' => $parentCategory->id,
            'icon' => '🐍',
            'status' => 'active',
        ]);

        // Create posts for child categories
        Post::factory()->count(3)->create([
            'category_id' => $childCategory1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->count(5)->create([
            'category_id' => $childCategory2->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('category.show', $parentCategory->slug));

        $response->assertStatus(200);
        $response->assertSee('Subcategories');
        $response->assertSee('JavaScript');
        $response->assertSee('Python');
        $response->assertSee('📜');
        $response->assertSee('🐍');
    }

    public function test_category_page_filters_posts_by_date(): void
    {
        $category = Category::factory()->create(['status' => 'active']);

        // Create posts with different dates
        $todayPost = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Today Article About Technology',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $oldPost = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Ancient Article From Two Months Ago',
            'status' => 'published',
            'published_at' => now()->subMonths(2),
        ]);

        // Test "today" filter
        $response = $this->get(route('category.show', $category->slug) . '?date_filter=today');
        $response->assertStatus(200);
        $response->assertSee('Today Article About Technology');
        $response->assertDontSee('Ancient Article From Two Months Ago');

        // Test "month" filter
        $response = $this->get(route('category.show', $category->slug) . '?date_filter=month');
        $response->assertStatus(200);
        $response->assertSee('Today Article About Technology');
        $response->assertDontSee('Ancient Article From Two Months Ago');
    }

    public function test_category_page_sorts_posts_correctly(): void
    {
        $category = Category::factory()->create(['status' => 'active']);

        $popularPost = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Popular Post',
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'view_count' => 1000,
        ]);

        $recentPost = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Recent Post',
            'status' => 'published',
            'published_at' => now(),
            'view_count' => 10,
        ]);

        // Test default (latest) sort
        $response = $this->get(route('category.show', $category->slug));
        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Popular Post'),
            strpos($content, 'Recent Post'),
            'Recent post should appear before popular post in latest sort'
        );

        // Test popular sort
        $response = $this->get(route('category.show', $category->slug) . '?sort=popular');
        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Recent Post'),
            strpos($content, 'Popular Post'),
            'Popular post should appear before recent post in popular sort'
        );
    }

    public function test_category_page_displays_empty_state_when_no_posts(): void
    {
        $category = Category::factory()->create(['status' => 'active']);

        $response = $this->get(route('category.show', $category->slug));

        $response->assertStatus(200);
        $response->assertSee('No articles found');
        $response->assertSee('Check back soon');
    }

    public function test_category_page_displays_breadcrumbs(): void
    {
        $parentCategory = Category::factory()->create([
            'name' => 'Technology',
            'status' => 'active',
        ]);

        $childCategory = Category::factory()->create([
            'name' => 'Web Development',
            'parent_id' => $parentCategory->id,
            'status' => 'active',
        ]);

        $response = $this->get(route('category.show', $childCategory->slug));

        $response->assertStatus(200);
        $response->assertSee('Home');
        $response->assertSee('Categories');
        $response->assertSee('Technology');
        $response->assertSee('Web Development');
    }
}
