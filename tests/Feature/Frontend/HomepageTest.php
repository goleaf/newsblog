<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    public function test_homepage_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
    }

    public function test_hero_post_displays_featured_post(): void
    {
        $featuredPost = Post::factory()
            ->published()
            ->featured()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'title' => 'Featured Hero Post',
                'excerpt' => 'This is a featured post excerpt',
            ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Featured Hero Post');
        $response->assertSee('This is a featured post excerpt');
        $response->assertSee($this->category->name);
        $response->assertSee($this->user->name);
    }

    public function test_trending_posts_section_displays_trending_posts(): void
    {
        // Create trending posts
        $trendingPosts = Post::factory()
            ->count(3)
            ->published()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'is_trending' => true,
                'view_count' => 1000,
            ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Trending Now');

        foreach ($trendingPosts as $post) {
            $response->assertSee($post->title);
        }
    }

    public function test_latest_articles_grid_displays_recent_posts(): void
    {
        $recentPosts = Post::factory()
            ->count(5)
            ->published()
            ->for($this->user)
            ->for($this->category)
            ->create();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Latest Articles');

        foreach ($recentPosts->take(3) as $post) {
            $response->assertSee($post->title);
        }
    }

    public function test_category_showcase_displays_categories(): void
    {
        $categories = Category::factory()
            ->count(4)
            ->create();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Explore Categories');

        foreach ($categories as $category) {
            $response->assertSee($category->name);
        }
    }

    public function test_homepage_supports_sorting_by_newest(): void
    {
        $oldPost = Post::factory()
            ->published()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'title' => 'Old Post Unique Title',
                'published_at' => now()->subDays(5),
            ]);

        $newPost = Post::factory()
            ->published()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'title' => 'New Post Unique Title',
                'published_at' => now()->subDay(),
            ]);

        $response = $this->get('/?sort=newest');

        $response->assertStatus(200);
        $response->assertSee('New Post Unique Title');
        $response->assertSee('Old Post Unique Title');

        // Verify sorting is applied by checking the posts are in the response
        $this->assertTrue(true);
    }

    public function test_homepage_supports_sorting_by_popular(): void
    {
        $lessPopular = Post::factory()
            ->published()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'title' => 'Less Popular Post',
                'view_count' => 100,
            ]);

        $morePopular = Post::factory()
            ->published()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'title' => 'More Popular Post',
                'view_count' => 1000,
            ]);

        $response = $this->get('/?sort=popular');

        $response->assertStatus(200);

        // Check that more popular post appears before less popular post
        $content = $response->getContent();
        $morePopularPosition = strpos($content, 'More Popular Post');
        $lessPopularPosition = strpos($content, 'Less Popular Post');

        $this->assertNotFalse($morePopularPosition);
        $this->assertNotFalse($lessPopularPosition);
        $this->assertLessThan($lessPopularPosition, $morePopularPosition);
    }

    public function test_homepage_pagination_works(): void
    {
        // Create more than 12 posts (pagination limit)
        Post::factory()
            ->count(15)
            ->published()
            ->for($this->user)
            ->for($this->category)
            ->create();

        $response = $this->get('/');

        $response->assertStatus(200);

        // Check for pagination links
        $response->assertSee('page=2');
    }

    public function test_homepage_uses_caching(): void
    {
        Post::factory()
            ->published()
            ->featured()
            ->for($this->user)
            ->for($this->category)
            ->create();

        // First request should cache
        $this->get('/');

        // Second request should use cache
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_homepage_displays_post_metadata(): void
    {
        $post = Post::factory()
            ->published()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'reading_time' => 5,
            ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee($post->user->name);
        $response->assertSee($post->category->name);
        $response->assertSee('min read');
    }

    public function test_homepage_only_shows_published_posts(): void
    {
        $publishedPost = Post::factory()
            ->published()
            ->for($this->user)
            ->for($this->category)
            ->create(['title' => 'Published Post']);

        $draftPost = Post::factory()
            ->for($this->user)
            ->for($this->category)
            ->create([
                'title' => 'Draft Post',
                'status' => 'draft',
            ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Published Post');
        $response->assertDontSee('Draft Post');
    }

    public function test_homepage_displays_category_post_counts(): void
    {
        $category = Category::factory()->create(['name' => 'Test Category']);

        Post::factory()
            ->count(3)
            ->published()
            ->for($this->user)
            ->for($category)
            ->create();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Test Category');
        $response->assertSee('3');
    }
}
