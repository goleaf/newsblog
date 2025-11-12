<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use App\Services\SearchIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Builders\PostTestBuilder;
use Tests\Concerns\TestsFuzzySearch;
use Tests\TestCase;

class FuzzySearchCachingTest extends TestCase
{
    use RefreshDatabase;
    use TestsFuzzySearch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFuzzySearch();
        $this->enableSearchCache();
    }

    public function test_search_results_are_cached(): void
    {
        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Cached Search Test')
            ->create();

        $results1 = $this->searchService->search('Cached Search Test');
        $results2 = $this->searchService->search('Cached Search Test');

        $this->assertNotEmpty($results1);
        $this->assertEquals($results1->count(), $results2->count());
        $this->assertEquals($results1->first()->id, $results2->first()->id);
    }

    public function test_search_results_cache_respects_filters(): void
    {
        $category1 = Category::factory()->create(['name' => 'Category One']);
        $category2 = Category::factory()->create(['name' => 'Category Two']);

        PostTestBuilder::make()
            ->published()
            ->withTitle('Test Post')
            ->inCategory($category1)
            ->create();

        PostTestBuilder::make()
            ->published()
            ->withTitle('Test Post')
            ->inCategory($category2)
            ->create();

        $results1 = $this->searchService->search('Test Post', filters: ['category' => 'Category One']);
        $results2 = $this->searchService->search('Test Post');

        $this->assertCount(1, $results1);
        $this->assertCount(2, $results2);
    }

    public function test_search_index_is_cached(): void
    {
        $indexService = app(SearchIndexService::class);

        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Index Cache Test')
            ->create();

        $index1 = $indexService->getIndex('posts');
        $index2 = $indexService->getIndex('posts');

        $this->assertNotEmpty($index1);
        $this->assertContains($post->id, array_column($index1, 'id'));
        $this->assertEquals($index1, $index2);
    }

    public function test_suggestions_are_cached(): void
    {
        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Suggestion Cache Test')
            ->create();

        $suggestions1 = $this->searchService->getSuggestions('Suggestion');
        $this->assertNotEmpty($suggestions1);

        $post->delete();

        $suggestions2 = $this->searchService->getSuggestions('Suggestion');
        $this->assertEquals($suggestions1, $suggestions2);
    }

    public function test_cache_invalidation_on_post_update(): void
    {
        $indexService = app(SearchIndexService::class);

        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Original Title')
            ->create();

        $this->searchService->search('Original Title');
        $indexService->getIndex('posts');

        $post->update(['title' => 'Updated Title']);

        $this->assertSearchFinds('Updated Title', $post->id);
    }

    public function test_cache_invalidation_on_post_delete(): void
    {
        $indexService = app(SearchIndexService::class);

        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Post To Delete')
            ->create();

        $this->searchService->search('Post To Delete');
        $indexService->getIndex('posts');

        $post->delete();

        $index = $indexService->getIndex('posts');
        $postIds = array_column($index, 'id');

        $this->assertNotContains($post->id, $postIds);
    }

    public function test_cache_invalidation_on_category_update(): void
    {
        $indexService = app(SearchIndexService::class);

        $category = Category::factory()->create(['name' => 'Original Category']);
        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Test Post')
            ->inCategory($category)
            ->create();

        $indexService->getIndex('posts');

        $category->update(['name' => 'Updated Category']);

        $index = $indexService->getIndex('posts');
        $postData = collect($index)->firstWhere('id', $post->id);

        $this->assertEquals('Updated Category', $postData['category']);
    }

    public function test_cache_invalidation_on_tag_update(): void
    {
        $indexService = app(SearchIndexService::class);

        $tag = Tag::create([
            'name' => 'Original Tag',
            'slug' => 'original-tag',
        ]);

        $indexBefore = $indexService->getIndex('tags');
        $tagDataBefore = collect($indexBefore)->firstWhere('id', $tag->id);

        $this->assertEquals('Original Tag', $tagDataBefore['name']);

        $tag->update(['name' => 'Updated Tag']);

        $index = $indexService->getIndex('tags');
        $tagData = collect($index)->firstWhere('id', $tag->id);

        $this->assertEquals('Updated Tag', $tagData['name']);
    }

    public function test_cache_can_be_disabled(): void
    {
        $this->disableSearchCache();

        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('No Cache Test')
            ->create();

        $this->assertSearchReturnsCount('No Cache Test', 1);

        $post->delete();

        $this->assertSearchEmpty('No Cache Test');
    }
}
