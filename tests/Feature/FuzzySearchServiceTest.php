<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Builders\PostTestBuilder;
use Tests\Concerns\TestsFuzzySearch;
use Tests\TestCase;

class FuzzySearchServiceTest extends TestCase
{
    use RefreshDatabase;
    use TestsFuzzySearch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFuzzySearch();
    }

    public function test_search_finds_posts_with_exact_match(): void
    {
        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Laravel Testing Guide')
            ->create();

        $this->assertSearchFinds('Laravel Testing Guide', $post->id);
    }

    public function test_search_finds_posts_with_fuzzy_match(): void
    {
        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Laravel Testing Guide')
            ->create();

        $this->assertSearchFinds('Laravl Testig Guid', $post->id);
    }

    public function test_search_respects_threshold_parameter(): void
    {
        PostTestBuilder::make()
            ->published()
            ->withTitle('Laravel Testing Guide')
            ->create();

        $resultsLowThreshold = $this->searchService->search('xyz', threshold: 20);
        $resultsHighThreshold = $this->searchService->search('xyz', threshold: 90);

        $this->assertGreaterThanOrEqual(
            count($resultsHighThreshold),
            count($resultsLowThreshold),
            'Lower threshold should return more or equal results'
        );
    }

    public function test_search_only_returns_published_posts(): void
    {
        $publishedPost = PostTestBuilder::make()
            ->published()
            ->withTitle('Published Post')
            ->create();

        PostTestBuilder::make()
            ->draft()
            ->withTitle('Draft Post')
            ->create();

        $results = $this->searchService->search('Post');

        $this->assertSearchReturnsCount('Post', 1);
        $this->assertEquals('Published Post', $results[0]->title);
    }

    public function test_search_excludes_scheduled_posts_not_yet_published(): void
    {
        PostTestBuilder::make()
            ->published()
            ->withTitle('Published Post')
            ->create();

        PostTestBuilder::make()
            ->scheduled()
            ->withTitle('Scheduled Post')
            ->create();

        $this->assertSearchReturnsCount('Post', 1);
    }

    public function test_search_filters_by_category_name(): void
    {
        $category1 = Category::factory()->create(['name' => 'PHP']);
        $category2 = Category::factory()->create(['name' => 'JavaScript']);

        $phpPost = PostTestBuilder::make()
            ->published()
            ->withTitle('Laravel Post')
            ->inCategory($category1)
            ->create();

        PostTestBuilder::make()
            ->published()
            ->withTitle('Laravel Post Two')
            ->inCategory($category2)
            ->create();

        $results = $this->searchService->search('Laravel', filters: ['category' => 'PHP']);

        $this->assertSearchReturnsCount('Laravel', 1);
        $this->assertSearchFinds('Laravel', $phpPost->id);
    }

    public function test_search_handles_empty_query_gracefully(): void
    {
        PostTestBuilder::make()
            ->published()
            ->withTitle('Test Post')
            ->create();

        $this->assertSearchEmpty('');
    }

    public function test_search_returns_empty_when_no_matches_found(): void
    {
        PostTestBuilder::make()
            ->published()
            ->withTitle('Laravel Testing')
            ->create();

        $this->assertSearchEmpty('nonexistentquery12345xyz');
    }

    public function test_search_respects_limit_parameter(): void
    {
        PostTestBuilder::make()
            ->published()
            ->withTitle('Laravel Post')
            ->count(20);

        $results = $this->searchService->search('Laravel', limit: 5);

        $this->assertCount(5, $results, 'Search should respect limit parameter');
    }

    public function test_search_posts_method_returns_only_posts(): void
    {
        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Laravel Framework Guide')
            ->create();

        $results = $this->searchService->searchPosts('Laravel Framework');

        $this->assertNotEmpty($results, 'searchPosts should return results');
        $this->assertEquals($post->id, $results->first()->id);
    }

    public function test_search_posts_accepts_threshold_option(): void
    {
        PostTestBuilder::make()
            ->published()
            ->withTitle('Laravel Testing')
            ->create();

        $results = $this->searchService->searchPosts('Laravel', ['threshold' => 80]);

        $this->assertNotEmpty($results, 'searchPosts should accept threshold option');
    }

    public function test_pre_filtering_handles_large_datasets(): void
    {
        $category = Category::factory()->create();

        // Create more than 1000 posts to test pre-filtering limit
        Post::factory()->count(1500)->create([
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $category->id,
        ]);

        $results = $this->searchService->search('test');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $results, 'Search should handle large datasets without errors');
    }

    public function test_pre_filtering_excludes_unpublished_posts(): void
    {
        $publishedPost = PostTestBuilder::make()
            ->published()
            ->withTitle('Published Post')
            ->create();

        PostTestBuilder::make()
            ->draft()
            ->withTitle('Draft Post')
            ->create();

        PostTestBuilder::make()
            ->scheduled()
            ->withTitle('Scheduled Post')
            ->create();

        $results = $this->searchService->search('Post');

        $this->assertSearchReturnsCount('Post', 1);
        $this->assertEquals($publishedPost->id, $results[0]->id);
    }
}
