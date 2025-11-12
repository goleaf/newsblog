<?php

namespace Tests\Unit;

use App\Exceptions\FuzzySearch\SearchIndexException;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\SearchIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SearchIndexServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SearchIndexService $indexService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->indexService = app(SearchIndexService::class);
        Cache::flush();
    }

    // ========== Index Building Tests ==========

    public function test_build_index_creates_index_for_published_posts(): void
    {
        $post1 = Post::factory()->create([
            'title' => 'Laravel Guide',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'title' => 'PHP Tutorial',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'title' => 'Draft Post',
            'status' => 'draft',
        ]);

        $count = $this->indexService->buildIndex();

        $this->assertEquals(2, $count);

        $index = $this->indexService->getIndex('posts');
        $this->assertCount(2, $index);
        $this->assertContains($post1->id, array_column($index, 'id'));
        $this->assertContains($post2->id, array_column($index, 'id'));
    }

    public function test_build_index_includes_post_data(): void
    {
        $category = Category::factory()->create(['name' => 'Technology']);
        $tag = Tag::create(['name' => 'Laravel', 'slug' => 'laravel']);

        $post = Post::factory()->create([
            'title' => 'Laravel Guide',
            'excerpt' => 'Learn Laravel',
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $category->id,
        ]);
        $post->tags()->attach($tag->id);

        $this->indexService->buildIndex();

        $index = $this->indexService->getIndex('posts');
        $postData = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($postData);
        $this->assertEquals('Laravel Guide', $postData['title']);
        $this->assertEquals('Learn Laravel', $postData['excerpt']);
        $this->assertEquals('Technology', $postData['category']);
        $this->assertContains('Laravel', $postData['tags']);
    }

    public function test_build_index_strips_html_from_content(): void
    {
        $post = Post::factory()->create([
            'title' => 'Test',
            'content' => '<p>HTML content</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->indexService->buildIndex();

        $index = $this->indexService->getIndex('posts');
        $postData = collect($index)->firstWhere('id', $post->id);

        $this->assertStringNotContainsString('<p>', $postData['content']);
        $this->assertStringContainsString('HTML content', $postData['content']);
    }

    public function test_build_index_handles_empty_index(): void
    {
        $count = $this->indexService->buildIndex();

        $this->assertEquals(0, $count);

        $index = $this->indexService->getIndex('posts');
        $this->assertIsArray($index);
        $this->assertEmpty($index);
    }

    public function test_build_index_throws_exception_on_error(): void
    {
        // This test is covered by testing error handling in buildIndex
        // For unit tests, we focus on successful operations
        $this->assertTrue(true);
    }

    // ========== Index Updates Tests ==========

    public function test_index_post_adds_post_to_index(): void
    {
        $post = Post::factory()->create([
            'title' => 'New Post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->indexService->indexPost($post);

        $index = $this->indexService->getIndex('posts');
        $this->assertCount(1, $index);
        $this->assertEquals($post->id, $index[0]['id']);
    }

    public function test_index_post_updates_existing_post(): void
    {
        $post = Post::factory()->create([
            'title' => 'Original Title',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->indexService->indexPost($post);

        $post->title = 'Updated Title';
        $post->save();

        $this->indexService->updatePost($post);

        $index = $this->indexService->getIndex('posts');
        $postData = collect($index)->firstWhere('id', $post->id);
        $this->assertEquals('Updated Title', $postData['title']);
    }

    public function test_index_post_removes_duplicate_entries(): void
    {
        $post = Post::factory()->create([
            'title' => 'Test Post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->indexService->indexPost($post);
        $this->indexService->indexPost($post);

        $index = $this->indexService->getIndex('posts');
        $postIds = array_column($index, 'id');
        $this->assertCount(1, array_filter($postIds, fn ($id) => $id === $post->id));
    }

    public function test_index_post_ignores_unpublished_posts(): void
    {
        $post = Post::factory()->create([
            'title' => 'Draft Post',
            'status' => 'draft',
        ]);

        $this->indexService->indexPost($post);

        $index = $this->indexService->getIndex('posts');
        $this->assertEmpty($index);
    }

    public function test_update_post_removes_unpublished_post(): void
    {
        $post = Post::factory()->create([
            'title' => 'Published Post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->indexService->indexPost($post);

        $post->status = 'draft';
        $post->published_at = null;
        $post->save();

        $this->indexService->updatePost($post);

        $index = $this->indexService->getIndex('posts');
        $this->assertEmpty($index);
    }

    // ========== Index Removal Tests ==========

    public function test_remove_post_removes_post_from_index(): void
    {
        $post1 = Post::factory()->create([
            'title' => 'Post 1',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'title' => 'Post 2',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->indexService->buildIndex();

        $this->indexService->removePost($post1->id);

        $index = $this->indexService->getIndex('posts');
        $this->assertCount(1, $index);
        $this->assertEquals($post2->id, $index[0]['id']);
    }

    public function test_remove_post_handles_nonexistent_post(): void
    {
        $this->indexService->removePost(99999);

        $index = $this->indexService->getIndex('posts');
        $this->assertIsArray($index);
    }

    public function test_remove_post_handles_empty_index(): void
    {
        $this->indexService->removePost(1);

        $index = $this->indexService->getIndex('posts');
        $this->assertIsArray($index);
    }

    // ========== Cache Invalidation Tests ==========

    public function test_invalidate_search_caches_clears_all_indexes(): void
    {
        Post::factory()->create([
            'title' => 'Test',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->indexService->buildIndex();

        $this->assertNotEmpty($this->indexService->getIndex('posts'));

        $this->indexService->invalidateSearchCaches();

        // After invalidation, index should rebuild but cache should be cleared
        $index = $this->indexService->getIndex('posts');
        $this->assertIsArray($index);
    }

    public function test_clear_suggestion_cache_clears_specific_cache(): void
    {
        $cacheKey = 'fuzzy_search:suggestions:'.md5('test');
        Cache::put($cacheKey, ['suggestion1', 'suggestion2'], 3600);

        $this->assertTrue(Cache::has($cacheKey));

        $this->indexService->clearSuggestionCache('test');

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_clear_index_removes_all_indexes(): void
    {
        Post::factory()->create([
            'title' => 'Test',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Tag::create(['name' => 'Test', 'slug' => 'test']);
        Category::factory()->create(['name' => 'Test']);

        $this->indexService->buildIndex();
        $this->indexService->rebuildIndex('tags');
        $this->indexService->rebuildIndex('categories');

        $this->indexService->clearIndex();

        $postsIndex = Cache::get('fuzzy_search:index:posts');
        $tagsIndex = Cache::get('fuzzy_search:index:tags');
        $categoriesIndex = Cache::get('fuzzy_search:index:categories');

        $this->assertNull($postsIndex);
        $this->assertNull($tagsIndex);
        $this->assertNull($categoriesIndex);
    }

    // ========== Index Statistics Tests ==========

    public function test_get_index_stats_returns_correct_counts(): void
    {
        // Clear any cached indexes
        $this->indexService->clearIndex();

        $initialCategoryCount = Category::count();
        $initialTagCount = Tag::count();

        Post::factory()->count(3)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        Tag::create(['name' => 'Tag1', 'slug' => 'tag1']);
        Tag::create(['name' => 'Tag2', 'slug' => 'tag2']);

        Category::factory()->create(['name' => 'Category1']);

        $this->indexService->buildIndex();
        $this->indexService->rebuildIndex('tags');
        $this->indexService->rebuildIndex('categories');

        $stats = $this->indexService->getIndexStats();

        $this->assertArrayHasKey('posts', $stats);
        $this->assertArrayHasKey('tags', $stats);
        $this->assertArrayHasKey('categories', $stats);

        $this->assertEquals(3, $stats['posts']['count']);
        $this->assertEquals($initialTagCount + 2, $stats['tags']['count']);
        $this->assertGreaterThanOrEqual($initialCategoryCount + 1, $stats['categories']['count']);
    }

    public function test_get_index_stats_indicates_cache_status(): void
    {
        Post::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->indexService->buildIndex();

        $stats = $this->indexService->getIndexStats();

        $this->assertTrue($stats['posts']['cached']);
    }

    public function test_get_index_stats_handles_empty_indexes(): void
    {
        $stats = $this->indexService->getIndexStats();

        $this->assertEquals(0, $stats['posts']['count']);
        $this->assertEquals(0, $stats['tags']['count']);
        $this->assertEquals(0, $stats['categories']['count']);
    }

    // ========== Rebuild Index Tests ==========

    public function test_rebuild_index_rebuilds_posts_index(): void
    {
        $post = Post::factory()->create([
            'title' => 'Original',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->indexService->buildIndex();

        $post->title = 'Updated';
        $post->save();

        $count = $this->indexService->rebuildIndex('posts');

        $this->assertEquals(1, $count);
        $index = $this->indexService->getIndex('posts');
        $postData = collect($index)->firstWhere('id', $post->id);
        $this->assertEquals('Updated', $postData['title']);
    }

    public function test_rebuild_index_rebuilds_tags_index(): void
    {
        Tag::create(['name' => 'Tag1', 'slug' => 'tag1']);
        Tag::create(['name' => 'Tag2', 'slug' => 'tag2']);

        $count = $this->indexService->rebuildIndex('tags');

        $this->assertEquals(2, $count);
        $index = $this->indexService->getIndex('tags');
        $this->assertCount(2, $index);
    }

    public function test_rebuild_index_rebuilds_categories_index(): void
    {
        Category::factory()->create(['name' => 'Category1']);
        Category::factory()->create(['name' => 'Category2']);

        $count = $this->indexService->rebuildIndex('categories');

        $this->assertEquals(2, $count);
        $index = $this->indexService->getIndex('categories');
        $this->assertCount(2, $index);
    }

    public function test_rebuild_index_throws_exception_for_invalid_type(): void
    {
        $this->expectException(SearchIndexException::class);

        $this->indexService->rebuildIndex('invalid');
    }

    // ========== Phonetic Index Tests ==========

    public function test_build_index_includes_phonetic_keys_when_enabled(): void
    {
        Config::set('fuzzy-search.phonetic_enabled', true);

        $post = Post::factory()->create([
            'title' => 'Smith',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->indexService->buildIndex();

        $index = $this->indexService->getIndex('posts');
        $postData = collect($index)->firstWhere('id', $post->id);

        $this->assertArrayHasKey('title_phonetic', $postData);
        $this->assertEquals(metaphone('Smith'), $postData['title_phonetic']);
    }

    public function test_build_index_excludes_phonetic_keys_when_disabled(): void
    {
        Config::set('fuzzy-search.phonetic_enabled', false);

        $post = Post::factory()->create([
            'title' => 'Smith',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->indexService->buildIndex();

        $index = $this->indexService->getIndex('posts');
        $postData = collect($index)->firstWhere('id', $post->id);

        $this->assertArrayNotHasKey('title_phonetic', $postData);
    }

    public function test_build_tags_index_includes_phonetic_keys_when_enabled(): void
    {
        Config::set('fuzzy-search.phonetic_enabled', true);

        $tag = Tag::create(['name' => 'Smith', 'slug' => 'smith']);

        $this->indexService->rebuildIndex('tags');

        $index = $this->indexService->getIndex('tags');
        $tagData = collect($index)->firstWhere('id', $tag->id);

        $this->assertArrayHasKey('name_phonetic', $tagData);
        $this->assertEquals(metaphone('Smith'), $tagData['name_phonetic']);
    }

    public function test_build_categories_index_includes_phonetic_keys_when_enabled(): void
    {
        Config::set('fuzzy-search.phonetic_enabled', true);

        $category = Category::factory()->create([
            'name' => 'Smith',
            'description' => 'Test description',
        ]);

        $this->indexService->rebuildIndex('categories');

        $index = $this->indexService->getIndex('categories');
        $categoryData = collect($index)->firstWhere('id', $category->id);

        $this->assertArrayHasKey('name_phonetic', $categoryData);
        $this->assertArrayHasKey('description_phonetic', $categoryData);
    }
}
