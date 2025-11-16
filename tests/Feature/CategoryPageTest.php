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
        $user = User::factory()->create();
        $category = Category::factory()->create(['status' => 'active']);

        // Create posts with different dates
        $todayPost = Post::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'title' => 'Today Article About Technology',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $oldPost = Post::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'title' => 'Ancient Article From Two Months Ago',
            'status' => 'published',
            'published_at' => now()->subMonths(2),
        ]);

        // Clear cache to ensure fresh data
        \Illuminate\Support\Facades\Cache::flush();

        // Test "today" filter
        $response = $this->get(route('category.show', $category->slug).'?date_filter=today');
        $response->assertStatus(200);
        $response->assertSee('Today Article About Technology');
        $response->assertDontSee('Ancient Article From Two Months Ago');

        // Test "month" filter
        $response = $this->get(route('category.show', $category->slug).'?date_filter=month');
        $response->assertStatus(200);
        $response->assertSee('Today Article About Technology');
        $response->assertDontSee('Ancient Article From Two Months Ago');
    }

    public function test_category_page_sorts_posts_correctly(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['status' => 'active']);

        $popularPost = Post::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'title' => 'Popular Post',
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'view_count' => 1000,
        ]);

        $recentPost = Post::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'title' => 'Recent Post',
            'status' => 'published',
            'published_at' => now(),
            'view_count' => 10,
        ]);

        // Clear cache to ensure fresh data
        \Illuminate\Support\Facades\Cache::flush();

        // Test default (latest) sort
        $response = $this->get(route('category.show', $category->slug));
        $response->assertStatus(200);
        $content = $response->getContent();
        $recentPos = strpos($content, 'Recent Post');
        $popularPos = strpos($content, 'Popular Post');
        $this->assertNotFalse($recentPos, 'Recent post should be in response');
        $this->assertNotFalse($popularPos, 'Popular post should be in response');
        $this->assertLessThan(
            $popularPos,
            $recentPos,
            'Recent post should appear before popular post in latest sort'
        );

        // Test popular sort
        $response = $this->get(route('category.show', $category->slug).'?sort=popular');
        $response->assertStatus(200);
        $content = $response->getContent();
        $recentPos = strpos($content, 'Recent Post');
        $popularPos = strpos($content, 'Popular Post');
        $this->assertNotFalse($recentPos, 'Recent post should be in response');
        $this->assertNotFalse($popularPos, 'Popular post should be in response');
        $this->assertLessThan(
            $recentPos,
            $popularPos,
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
        $response->assertSee('Technology');
        $response->assertSee('Web Development');
    }

    public function test_category_page_includes_posts_from_subcategories(): void
    {
        $user = User::factory()->create();
        $parentCategory = Category::factory()->create([
            'name' => 'Technology',
            'status' => 'active',
        ]);

        $childCategory = Category::factory()->create([
            'name' => 'Web Development',
            'parent_id' => $parentCategory->id,
            'status' => 'active',
        ]);

        // Create posts in parent category
        $parentPost = Post::factory()->create([
            'category_id' => $parentCategory->id,
            'user_id' => $user->id,
            'title' => 'Parent Category Post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Create posts in child category
        $childPost = Post::factory()->create([
            'category_id' => $childCategory->id,
            'user_id' => $user->id,
            'title' => 'Child Category Post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('category.show', $parentCategory->slug));

        $response->assertStatus(200);
        $response->assertSee('Parent Category Post');
        $response->assertSee('Child Category Post');
    }

    public function test_category_page_paginates_posts_with_15_per_page(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['status' => 'active']);

        // Create 20 posts
        Post::factory()->count(20)->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Clear cache to ensure fresh data
        \Illuminate\Support\Facades\Cache::flush();

        $response = $this->get(route('category.show', $category->slug));

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertNotNull($posts, 'Posts should be in view data');
        $this->assertEquals(15, $posts->count(), 'Should have 15 posts per page');
        $this->assertTrue($posts->hasMorePages(), 'Should have more pages');
    }

    public function test_category_page_includes_seo_meta_tags(): void
    {
        $category = Category::factory()->create([
            'name' => 'Technology',
            'meta_title' => 'Technology News',
            'meta_description' => 'Latest technology news and articles',
            'status' => 'active',
        ]);

        $response = $this->get(route('category.show', $category->slug));

        $response->assertStatus(200);
        $response->assertSee('Technology News', false);
        $response->assertSee('Latest technology news and articles', false);
        $response->assertSee('og:title', false);
        $response->assertSee('og:description', false);
        $response->assertSee('twitter:card', false);
    }

    public function test_category_page_includes_breadcrumb_structured_data(): void
    {
        $category = Category::factory()->create([
            'name' => 'Technology',
            'status' => 'active',
        ]);

        $response = $this->get(route('category.show', $category->slug));

        $response->assertStatus(200);
        $response->assertSee('BreadcrumbList', false);
        $response->assertSee('application/ld+json', false);
    }

    public function test_category_page_includes_collection_page_structured_data(): void
    {
        $category = Category::factory()->create([
            'name' => 'Technology',
            'description' => 'Technology articles',
            'status' => 'active',
        ]);

        $response = $this->get(route('category.show', $category->slug));

        $response->assertStatus(200);
        $response->assertSee('CollectionPage', false);
        $response->assertSee('Technology', false);
    }
}
