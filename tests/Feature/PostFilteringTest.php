<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Category $category;

    protected Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['name' => 'Technology']);
        $this->tag = Tag::factory()->create(['name' => 'Laravel']);
    }

    #[Test]
    public function it_displays_posts_in_category_with_default_sorting(): void
    {
        // Create posts with different dates and view counts
        $post1 = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Latest Post',
            'published_at' => now(),
            'view_count' => 10,
        ]);

        $post2 = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Older Post',
            'published_at' => now()->subDays(5),
            'view_count' => 100,
        ]);

        $response = $this->get(route('category.show', $this->category->slug));

        $response->assertOk();
        $response->assertSeeInOrder(['Latest Post', 'Older Post']); // Latest first by default
    }

    #[Test]
    public function it_sorts_posts_by_popularity(): void
    {
        // Requirement 26.2: Sort by Popular (view count)
        $post1 = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Less Popular',
            'view_count' => 10,
        ]);

        $post2 = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Most Popular',
            'view_count' => 100,
        ]);

        $response = $this->get(route('category.show', [$this->category->slug, 'sort' => 'popular']));

        $response->assertOk();
        $response->assertSeeInOrder(['Most Popular', 'Less Popular']);
    }

    #[Test]
    public function it_sorts_posts_by_oldest(): void
    {
        // Requirement 26.3: Sort by Oldest
        $post1 = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Newest Post',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Oldest Post',
            'published_at' => now()->subDays(10),
        ]);

        $response = $this->get(route('category.show', [$this->category->slug, 'sort' => 'oldest']));

        $response->assertOk();
        $response->assertSeeInOrder(['Oldest Post', 'Newest Post']);
    }

    #[Test]
    public function it_filters_posts_by_today(): void
    {
        // Requirement 26.4: Date filter - Today
        $todayPost = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Today Post',
            'published_at' => now(),
        ]);

        $yesterdayPost = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Yesterday Post',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('category.show', [$this->category->slug, 'date_filter' => 'today']));

        $response->assertOk();
        $response->assertSee('Today Post');
        $response->assertDontSee('Yesterday Post');
    }

    #[Test]
    public function it_filters_posts_by_this_week(): void
    {
        // Requirement 26.4: Date filter - This Week
        $thisWeekPost = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'This Week Post',
            'published_at' => now()->subDays(3),
        ]);

        $lastMonthPost = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Last Month Post',
            'published_at' => now()->subMonth(),
        ]);

        $response = $this->get(route('category.show', [$this->category->slug, 'date_filter' => 'week']));

        $response->assertOk();
        $response->assertSee('This Week Post');
        $response->assertDontSee('Last Month Post');
    }

    #[Test]
    public function it_filters_posts_by_this_month(): void
    {
        // Requirement 26.4: Date filter - This Month
        $thisMonthPost = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'This Month Post',
            'published_at' => now()->subDays(15),
        ]);

        $lastYearPost = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Last Year Post',
            'published_at' => now()->subYear(),
        ]);

        $response = $this->get(route('category.show', [$this->category->slug, 'date_filter' => 'month']));

        $response->assertOk();
        $response->assertSee('This Month Post');
        $response->assertDontSee('Last Year Post');
    }

    #[Test]
    public function it_returns_json_for_ajax_requests(): void
    {
        // Requirement 26.1: AJAX-based filtering without page reload
        Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Test Post',
        ]);

        $response = $this->getJson(route('category.show', $this->category->slug));

        $response->assertOk();
        $response->assertJsonStructure(['html', 'pagination']);
    }

    #[Test]
    public function it_persists_filters_in_url_parameters(): void
    {
        // Requirement 26.5: URL parameter persistence for shareability
        Post::factory()->published()->create([
            'category_id' => $this->category->id,
        ]);

        $response = $this->get(route('category.show', [
            $this->category->slug,
            'sort' => 'popular',
            'date_filter' => 'week',
        ]));

        $response->assertOk();
        // The URL should contain the query parameters
        $this->assertEquals('popular', request()->query('sort'));
        $this->assertEquals('week', request()->query('date_filter'));
    }

    #[Test]
    public function it_filters_and_sorts_posts_on_tag_pages(): void
    {
        // Test that filtering works on tag pages too
        $post1 = Post::factory()->published()->create([
            'title' => 'Popular Tagged Post',
            'view_count' => 100,
            'published_at' => now()->subDays(5),
        ]);
        $post1->tags()->attach($this->tag);

        $post2 = Post::factory()->published()->create([
            'title' => 'Less Popular Tagged Post',
            'view_count' => 10,
            'published_at' => now(),
        ]);
        $post2->tags()->attach($this->tag);

        $response = $this->get(route('tag.show', [$this->tag->slug, 'sort' => 'popular']));

        $response->assertOk();
        $response->assertSeeInOrder(['Popular Tagged Post', 'Less Popular Tagged Post']);
    }

    #[Test]
    public function it_combines_sort_and_date_filters(): void
    {
        // Test combining multiple filters
        $post1 = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Recent Popular',
            'view_count' => 100,
            'published_at' => now()->subDays(2),
        ]);

        $post2 = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Recent Less Popular',
            'view_count' => 50,
            'published_at' => now()->subDays(3),
        ]);

        $post3 = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'title' => 'Old Popular',
            'view_count' => 200,
            'published_at' => now()->subMonth(),
        ]);

        $response = $this->get(route('category.show', [
            $this->category->slug,
            'sort' => 'popular',
            'date_filter' => 'week',
        ]));

        $response->assertOk();
        $response->assertSee('Recent Popular');
        $response->assertSee('Recent Less Popular');
        $response->assertDontSee('Old Popular'); // Filtered out by date
    }
}
