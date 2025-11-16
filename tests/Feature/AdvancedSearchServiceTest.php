<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\AdvancedSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdvancedSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdvancedSearchService $service;

    protected User $user;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AdvancedSearchService::class);
        $this->user = User::factory()->create(['name' => 'John Doe']);
        $this->category = Category::factory()->create(['name' => 'Technology']);
    }

    #[Test]
    public function it_searches_posts_by_query(): void
    {
        Post::factory()->published()->create([
            'title' => 'Laravel Testing Guide',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        Post::factory()->published()->create([
            'title' => 'PHP Best Practices',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $results = $this->service->search('Laravel');

        $this->assertCount(1, $results);
        $this->assertEquals('Laravel Testing Guide', $results->first()->title);
    }

    #[Test]
    public function it_filters_posts_by_date_range(): void
    {
        // Requirement 39.1: Date range filtering
        Post::factory()->published()->create([
            'title' => 'Recent Post',
            'published_at' => now()->subDays(5),
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        Post::factory()->published()->create([
            'title' => 'Old Post',
            'published_at' => now()->subDays(30),
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $results = $this->service->search('', [
            'date_from' => now()->subDays(10)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals('Recent Post', $results->first()->title);
    }

    #[Test]
    public function it_includes_end_date_in_date_range_filter(): void
    {
        // Test that posts published on the end date are included
        $endDate = now()->startOfDay();

        Post::factory()->published()->create([
            'title' => 'End Date Post',
            'published_at' => $endDate->copy()->addHours(12),
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $results = $this->service->search('', [
            'date_from' => $endDate->copy()->subDays(1)->format('Y-m-d'),
            'date_to' => $endDate->format('Y-m-d'),
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals('End Date Post', $results->first()->title);
    }

    #[Test]
    public function it_filters_posts_by_author(): void
    {
        // Requirement 39.2: Author filter
        $author1 = User::factory()->create(['name' => 'Author One']);
        $author2 = User::factory()->create(['name' => 'Author Two']);

        Post::factory()->published()->create([
            'title' => 'Post by Author 1',
            'user_id' => $author1->id,
            'category_id' => $this->category->id,
        ]);

        Post::factory()->published()->create([
            'title' => 'Post by Author 2',
            'user_id' => $author2->id,
            'category_id' => $this->category->id,
        ]);

        $results = $this->service->search('', ['author' => $author1->id]);

        $this->assertCount(1, $results);
        $this->assertEquals('Post by Author 1', $results->first()->title);
    }

    #[Test]
    public function it_filters_posts_by_category_including_subcategories(): void
    {
        // Requirement 39.3: Category filter with subcategory inclusion
        $parentCategory = Category::factory()->create(['name' => 'Programming']);
        $childCategory = Category::factory()->create([
            'name' => 'PHP',
            'parent_id' => $parentCategory->id,
        ]);
        $grandchildCategory = Category::factory()->create([
            'name' => 'Laravel',
            'parent_id' => $childCategory->id,
        ]);

        Post::factory()->published()->create([
            'title' => 'Parent Category Post',
            'category_id' => $parentCategory->id,
            'user_id' => $this->user->id,
        ]);

        Post::factory()->published()->create([
            'title' => 'Child Category Post',
            'category_id' => $childCategory->id,
            'user_id' => $this->user->id,
        ]);

        Post::factory()->published()->create([
            'title' => 'Grandchild Category Post',
            'category_id' => $grandchildCategory->id,
            'user_id' => $this->user->id,
        ]);

        Post::factory()->published()->create([
            'title' => 'Other Category Post',
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
        ]);

        $results = $this->service->search('', ['category' => $parentCategory->id]);

        $this->assertCount(3, $results);
        $this->assertTrue($results->contains('title', 'Parent Category Post'));
        $this->assertTrue($results->contains('title', 'Child Category Post'));
        $this->assertTrue($results->contains('title', 'Grandchild Category Post'));
        $this->assertFalse($results->contains('title', 'Other Category Post'));
    }

    #[Test]
    public function it_filters_posts_by_multiple_tags_with_and_logic(): void
    {
        // Requirement 39.4: Tag multi-select filter with AND logic
        $tag1 = Tag::factory()->create(['name' => 'Laravel']);
        $tag2 = Tag::factory()->create(['name' => 'Testing']);
        $tag3 = Tag::factory()->create(['name' => 'PHP']);

        $post1 = Post::factory()->published()->create([
            'title' => 'Post with Laravel and Testing',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);
        $post1->tags()->attach([$tag1->id, $tag2->id]);

        $post2 = Post::factory()->published()->create([
            'title' => 'Post with only Laravel',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);
        $post2->tags()->attach([$tag1->id]);

        $post3 = Post::factory()->published()->create([
            'title' => 'Post with Laravel, Testing, and PHP',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);
        $post3->tags()->attach([$tag1->id, $tag2->id, $tag3->id]);

        // Filter by both Laravel AND Testing tags
        $results = $this->service->search('', ['tags' => [$tag1->id, $tag2->id]]);

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('title', 'Post with Laravel and Testing'));
        $this->assertTrue($results->contains('title', 'Post with Laravel, Testing, and PHP'));
        $this->assertFalse($results->contains('title', 'Post with only Laravel'));
    }

    #[Test]
    public function it_combines_multiple_filters_with_and_logic(): void
    {
        // Requirement 39.4: Multiple filters combined with AND logic
        $author = User::factory()->create(['name' => 'Specific Author']);
        $tag = Tag::factory()->create(['name' => 'Laravel']);

        $matchingPost = Post::factory()->published()->create([
            'title' => 'Matching Post',
            'user_id' => $author->id,
            'category_id' => $this->category->id,
            'published_at' => now()->subDays(5),
        ]);
        $matchingPost->tags()->attach($tag->id);

        Post::factory()->published()->create([
            'title' => 'Wrong Author',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'published_at' => now()->subDays(5),
        ])->tags()->attach($tag->id);

        Post::factory()->published()->create([
            'title' => 'Wrong Date',
            'user_id' => $author->id,
            'category_id' => $this->category->id,
            'published_at' => now()->subDays(30),
        ])->tags()->attach($tag->id);

        $results = $this->service->search('', [
            'author' => $author->id,
            'tags' => [$tag->id],
            'date_from' => now()->subDays(10)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals('Matching Post', $results->first()->title);
    }

    #[Test]
    public function it_highlights_matching_terms_in_results(): void
    {
        Post::factory()->published()->create([
            'title' => 'Laravel Testing Guide',
            'excerpt' => 'Learn how to test Laravel applications',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $results = $this->service->search('Laravel');

        $this->assertStringContainsString('<mark class="search-highlight">Laravel</mark>', $results->first()->highlighted_title);
        $this->assertStringContainsString('<mark class="search-highlight">Laravel</mark>', $results->first()->highlighted_excerpt);
    }

    #[Test]
    public function it_returns_authors_with_published_posts(): void
    {
        // Requirement 39.2: Display dropdown of all authors with published posts
        $authorWithPosts = User::factory()->create(['name' => 'Active Author']);
        $authorWithoutPosts = User::factory()->create(['name' => 'Inactive Author']);

        Post::factory()->published()->create([
            'user_id' => $authorWithPosts->id,
            'category_id' => $this->category->id,
        ]);

        Post::factory()->create([
            'status' => 'draft',
            'user_id' => $authorWithoutPosts->id,
            'category_id' => $this->category->id,
        ]);

        $authors = $this->service->getAuthorsWithPosts();

        $this->assertCount(1, $authors);
        $this->assertEquals('Active Author', $authors->first()->name);
    }

    #[Test]
    public function it_returns_tags_with_posts(): void
    {
        $tagWithPosts = Tag::factory()->create(['name' => 'Active Tag']);
        $tagWithoutPosts = Tag::factory()->create(['name' => 'Unused Tag']);

        $post = Post::factory()->published()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);
        $post->tags()->attach($tagWithPosts->id);

        $tags = $this->service->getTagsWithPosts();

        $this->assertCount(1, $tags);
        $this->assertEquals('Active Tag', $tags->first()->name);
    }

    #[Test]
    public function it_counts_active_filters(): void
    {
        // Requirement 39.5: Display active filter count
        $this->assertEquals(0, $this->service->countActiveFilters([]));

        $this->assertEquals(1, $this->service->countActiveFilters(['author' => 1]));

        $this->assertEquals(1, $this->service->countActiveFilters([
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ]));

        $this->assertEquals(3, $this->service->countActiveFilters([
            'author' => 1,
            'category' => 2,
            'tags' => [1, 2],
        ]));
    }

    #[Test]
    public function it_counts_total_results_without_pagination(): void
    {
        Post::factory()->published()->count(25)->create([
            'title' => 'Laravel Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $count = $this->service->countResults('Laravel');

        $this->assertEquals(25, $count);
    }

    #[Test]
    public function it_searches_in_title_content_and_excerpt(): void
    {
        Post::factory()->published()->create([
            'title' => 'Laravel Guide',
            'content' => 'Some content',
            'excerpt' => 'Some excerpt',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        Post::factory()->published()->create([
            'title' => 'PHP Guide',
            'content' => 'Learn Laravel framework',
            'excerpt' => 'Some excerpt',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        Post::factory()->published()->create([
            'title' => 'Testing Guide',
            'content' => 'Some content',
            'excerpt' => 'Laravel testing tips',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $results = $this->service->search('Laravel');

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_sorts_results_by_relevance(): void
    {
        // Exact title match should rank highest
        Post::factory()->published()->create([
            'title' => 'Laravel',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'published_at' => now()->subDays(10),
        ]);

        // Title contains match
        Post::factory()->published()->create([
            'title' => 'Laravel Testing Guide',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'published_at' => now()->subDays(5),
        ]);

        // Excerpt match
        Post::factory()->published()->create([
            'title' => 'Testing Guide',
            'excerpt' => 'Learn Laravel',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'published_at' => now(),
        ]);

        $results = $this->service->search('Laravel');

        $this->assertEquals('Laravel', $results->first()->title);
        $this->assertEquals('Laravel Testing Guide', $results->get(1)->title);
        $this->assertEquals('Testing Guide', $results->get(2)->title);
    }

    #[Test]
    public function it_only_returns_published_posts(): void
    {
        Post::factory()->published()->create([
            'title' => 'Published Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        Post::factory()->create([
            'title' => 'Draft Post',
            'status' => 'draft',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        Post::factory()->create([
            'title' => 'Scheduled Post',
            'status' => 'scheduled',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $results = $this->service->search('Post');

        $this->assertCount(1, $results);
        $this->assertEquals('Published Post', $results->first()->title);
    }

    #[Test]
    public function it_eager_loads_relationships(): void
    {
        Post::factory()->published()->create([
            'title' => 'Test Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $results = $this->service->search('Test');

        // Check that relationships are loaded
        $this->assertTrue($results->first()->relationLoaded('user'));
        $this->assertTrue($results->first()->relationLoaded('category'));
        $this->assertTrue($results->first()->relationLoaded('tags'));
    }

    #[Test]
    public function it_paginates_results(): void
    {
        Post::factory()->published()->count(20)->create([
            'title' => 'Laravel Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $results = $this->service->search('Laravel', [], 10);

        $this->assertCount(10, $results);
        $this->assertEquals(20, $results->total());
        $this->assertEquals(2, $results->lastPage());
    }

    #[Test]
    public function it_handles_empty_query_with_filters(): void
    {
        Post::factory()->published()->count(5)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $results = $this->service->search('', ['author' => $this->user->id]);

        $this->assertCount(5, $results);
    }

    #[Test]
    public function it_handles_invalid_category_id_gracefully(): void
    {
        Post::factory()->published()->create([
            'title' => 'Test Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $results = $this->service->search('', ['category' => 99999]);

        $this->assertCount(0, $results);
    }

    #[Test]
    public function it_escapes_html_in_highlighted_text(): void
    {
        Post::factory()->published()->create([
            'title' => '<script>alert("xss")</script> Laravel Guide',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $results = $this->service->search('Laravel');

        $this->assertStringNotContainsString('<script>', $results->first()->highlighted_title);
        $this->assertStringContainsString('&lt;script&gt;', $results->first()->highlighted_title);
    }
}
