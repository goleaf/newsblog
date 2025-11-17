<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\FilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FilterService $filterService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filterService = new FilterService;
    }

    public function test_filter_by_category(): void
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Post::factory()->count(3)->create(['category_id' => $category1->id, 'status' => PostStatus::Published, 'published_at' => now()->subDay()]);
        Post::factory()->count(2)->create(['category_id' => $category2->id, 'status' => PostStatus::Published, 'published_at' => now()->subDay()]);

        $query = Post::query()->published();
        $filtered = $this->filterService->filterByCategory($query, $category1->id);

        $this->assertEquals(3, $filtered->count());
    }

    public function test_filter_by_author(): void
    {
        $author1 = User::factory()->create();
        $author2 = User::factory()->create();

        Post::factory()->count(4)->create(['user_id' => $author1->id, 'status' => PostStatus::Published, 'published_at' => now()->subDay()]);
        Post::factory()->count(2)->create(['user_id' => $author2->id, 'status' => PostStatus::Published, 'published_at' => now()->subDay()]);

        $query = Post::query()->published();
        $filtered = $this->filterService->filterByAuthor($query, $author1->id);

        $this->assertEquals(4, $filtered->count());
    }

    public function test_filter_by_tags(): void
    {
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $post1 = Post::factory()->create(['status' => PostStatus::Published, 'published_at' => now()->subDay()]);
        $post1->tags()->attach($tag1);

        $post2 = Post::factory()->create(['status' => PostStatus::Published, 'published_at' => now()->subDay()]);
        $post2->tags()->attach($tag2);

        $post3 = Post::factory()->create(['status' => PostStatus::Published, 'published_at' => now()->subDay()]);
        $post3->tags()->attach([$tag1->id, $tag2->id]);

        $query = Post::query()->published();
        $filtered = $this->filterService->filterByTags($query, [$tag1->id]);

        $this->assertEquals(2, $filtered->count());
    }

    public function test_filter_by_date_range(): void
    {
        Post::factory()->create([
            'status' => PostStatus::Published,
            'published_at' => now()->subDays(10),
        ]);

        Post::factory()->create([
            'status' => PostStatus::Published,
            'published_at' => now()->subDays(5),
        ]);

        Post::factory()->create([
            'status' => PostStatus::Published,
            'published_at' => now()->subDays(1),
        ]);

        $query = Post::query()->published();
        $filtered = $this->filterService->filterByDateRange(
            $query,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals(2, $filtered->count());
    }

    public function test_filter_by_reading_time(): void
    {
        // Create posts with specific reading times (200 words per minute)
        // 3 min = 600 words, 7 min = 1400 words, 15 min = 3000 words, 25 min = 5000 words
        Post::factory()->create([
            'status' => PostStatus::Published,
            'content' => str_repeat('word ', 600),
            'published_at' => now()->subDay(),
        ]);
        Post::factory()->create([
            'status' => PostStatus::Published,
            'content' => str_repeat('word ', 1400),
            'published_at' => now()->subDay(),
        ]);
        Post::factory()->create([
            'status' => PostStatus::Published,
            'content' => str_repeat('word ', 3000),
            'published_at' => now()->subDay(),
        ]);
        Post::factory()->create([
            'status' => PostStatus::Published,
            'content' => str_repeat('word ', 5000),
            'published_at' => now()->subDay(),
        ]);

        $query = Post::query()->published();
        $filtered = $this->filterService->filterByReadingTime($query, 5, 20)->get();

        $this->assertEquals(2, $filtered->count());
    }

    public function test_apply_multiple_filters(): void
    {
        $category = Category::factory()->create();
        $author = User::factory()->create();
        $tag = Tag::factory()->create();

        $matchingPost = Post::factory()->create([
            'category_id' => $category->id,
            'user_id' => $author->id,
            'status' => PostStatus::Published,
            'content' => str_repeat('word ', 2000), // 10 min reading time
            'published_at' => now()->subDays(3),
        ]);
        $matchingPost->tags()->attach($tag);

        Post::factory()->create([
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $query = Post::query()->published();
        $filtered = $this->filterService->applyFilters($query, [
            'category' => $category->id,
            'author' => $author->id,
            'tags' => [$tag->id],
            'reading_time_min' => 5,
            'reading_time_max' => 15,
        ])->get();

        $this->assertEquals(1, $filtered->count());
        $this->assertEquals($matchingPost->id, $filtered->first()->id);
    }

    public function test_apply_sorting(): void
    {
        $old = Post::factory()->create([
            'status' => PostStatus::Published,
            'published_at' => now()->subDays(10),
            'view_count' => 100,
        ]);

        $recent = Post::factory()->create([
            'status' => PostStatus::Published,
            'published_at' => now()->subDays(1),
            'view_count' => 50,
        ]);

        // Test newest sort
        $query = Post::query()->published();
        $sorted = $this->filterService->applySorting($query, 'newest');
        $this->assertEquals($recent->id, $sorted->first()->id);

        // Test oldest sort
        $query = Post::query()->published();
        $sorted = $this->filterService->applySorting($query, 'oldest');
        $this->assertEquals($old->id, $sorted->first()->id);

        // Test popular sort
        $query = Post::query()->published();
        $sorted = $this->filterService->applySorting($query, 'popular');
        $this->assertEquals($old->id, $sorted->first()->id);
    }

    public function test_count_active_filters(): void
    {
        $filters = [
            'category' => 1,
            'author' => 2,
            'tags' => [1, 2],
            'date_from' => '2024-01-01',
            'reading_time_min' => 5,
        ];

        $count = $this->filterService->countActiveFilters($filters);

        $this->assertEquals(5, $count);
    }

    public function test_get_filter_options(): void
    {
        Category::factory()->count(3)->create();
        $author = User::factory()->create();
        Post::factory()->create([
            'user_id' => $author->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        // Create tags with unique slugs
        for ($i = 1; $i <= 5; $i++) {
            Tag::factory()->create([
                'name' => 'Tag '.$i,
                'slug' => 'tag-'.$i,
            ]);
        }

        $options = $this->filterService->getFilterOptions();

        $this->assertArrayHasKey('categories', $options);
        $this->assertArrayHasKey('authors', $options);
        $this->assertArrayHasKey('tags', $options);
        $this->assertArrayHasKey('reading_time_ranges', $options);

        $this->assertGreaterThanOrEqual(3, $options['categories']->count());
        $this->assertGreaterThanOrEqual(1, $options['authors']->count());
        $this->assertGreaterThanOrEqual(5, $options['tags']->count());
    }

    public function test_get_sorting_options(): void
    {
        $options = $this->filterService->getSortingOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('newest', $options);
        $this->assertArrayHasKey('oldest', $options);
        $this->assertArrayHasKey('popular', $options);
        $this->assertArrayHasKey('engagement', $options);
    }
}
