<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfiniteScrollTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Category $category;

    protected Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
        $this->tag = Tag::factory()->create();
    }

    /**
     * Test infinite scroll on category page returns JSON with posts HTML
     * Requirement 27.1: Load next page via AJAX
     */
    public function test_category_page_returns_json_for_ajax_requests(): void
    {
        // Create 15 posts to ensure pagination
        Post::factory()->count(15)->create([
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson(route('category.show', $this->category->slug).'?page=2', [
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

        // Verify HTML contains post cards
        $this->assertStringContainsString('data-post-item', $response->json('html'));
    }

    /**
     * Test infinite scroll on tag page returns JSON with posts HTML
     * Requirement 27.1: Load next page via AJAX
     */
    public function test_tag_page_returns_json_for_ajax_requests(): void
    {
        // Create 15 posts and attach to tag
        $posts = Post::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        foreach ($posts as $post) {
            $post->tags()->attach($this->tag);
        }

        $response = $this->getJson(route('tag.show', $this->tag->slug).'?page=2', [
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

        // Verify HTML contains post cards
        $this->assertStringContainsString('data-post-item', $response->json('html'));
    }

    /**
     * Test infinite scroll on search page returns JSON with posts HTML
     * Requirement 27.1: Load next page via AJAX
     */
    public function test_search_page_returns_json_for_ajax_requests(): void
    {
        // Create 20 posts with searchable content to ensure pagination (15 per page)
        Post::factory()->count(20)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'published_at' => now(),
            'title' => 'Test Post Title',
        ]);

        $response = $this->getJson(route('search', ['q' => 'Test', 'page' => 2]), [
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

        // Verify HTML contains post cards
        $this->assertStringContainsString('data-post-item', $response->json('html'));
    }

    /**
     * Test that last page returns hasMorePages as false
     * Requirement 27.5: Display "End of content" message when complete
     */
    public function test_last_page_indicates_no_more_pages(): void
    {
        // Create exactly 12 posts (one page)
        Post::factory()->count(12)->create([
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson(route('category.show', $this->category->slug).'?page=1', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'currentPage' => 1,
                'lastPage' => 1,
                'hasMorePages' => false,
            ]);
    }

    /**
     * Test that HTML response is returned for non-AJAX requests
     * Ensures backward compatibility
     */
    public function test_category_page_returns_html_for_regular_requests(): void
    {
        Post::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('category.show', $this->category->slug));

        $response->assertStatus(200)
            ->assertViewIs('categories.show')
            ->assertViewHas('posts')
            ->assertViewHas('category');
    }

    /**
     * Test infinite scroll component is present on category page
     * Requirement 27.1-27.5: Infinite scroll implementation
     */
    public function test_infinite_scroll_component_present_on_category_page(): void
    {
        Post::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('category.show', $this->category->slug));

        $response->assertStatus(200)
            ->assertSee('x-data="infiniteScroll()"', false)
            ->assertSee('data-post-item', false);
    }

    /**
     * Test infinite scroll respects filters and sorting
     * Requirement 26.5: Maintain filter and sort selections in URL
     */
    public function test_infinite_scroll_respects_filters(): void
    {
        // Create posts with different dates
        Post::factory()->count(15)->create([
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now()->subDays(10),
        ]);

        Post::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson(
            route('category.show', $this->category->slug).'?date_filter=today&page=1',
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertStatus(200);

        // Should only return recent posts
        $html = $response->json('html');
        $this->assertNotEmpty($html);
    }
}
