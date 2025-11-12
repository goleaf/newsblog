<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\SearchIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SearchIndexIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected SearchIndexService $searchIndexService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchIndexService = app(SearchIndexService::class);
        Cache::flush();
    }

    /**
     * Test that post creation triggers index update
     * Requirements: 6.1
     */
    public function test_post_creation_triggers_index_update(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a published post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'title' => 'Test Post for Indexing',
        ]);

        // Verify post is indexed
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($indexedPost, 'Post should be indexed after creation');
        $this->assertEquals('Test Post for Indexing', $indexedPost['title']);
        $this->assertEquals($post->slug, $indexedPost['slug']);
    }

    /**
     * Test that draft post creation does not trigger index update
     * Requirements: 6.1
     */
    public function test_draft_post_creation_does_not_trigger_index_update(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a draft post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        // Verify post is NOT indexed
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNull($indexedPost, 'Draft post should not be indexed');
    }

    /**
     * Test that post update triggers index update
     * Requirements: 6.2
     */
    public function test_post_update_triggers_index_update(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a published post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'title' => 'Original Title',
        ]);

        // Verify initial indexing
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);
        $this->assertEquals('Original Title', $indexedPost['title']);

        // Update the post
        $post->update([
            'title' => 'Updated Title',
            'excerpt' => 'Updated excerpt',
        ]);

        // Verify index is updated
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($indexedPost, 'Post should remain indexed after update');
        $this->assertEquals('Updated Title', $indexedPost['title']);
        $this->assertEquals('Updated excerpt', $indexedPost['excerpt']);
    }

    /**
     * Test that updating post to draft removes it from index
     * Requirements: 6.2
     */
    public function test_post_update_to_draft_removes_from_index(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a published post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Verify post is indexed
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);
        $this->assertNotNull($indexedPost, 'Published post should be indexed');

        // Update to draft
        $post->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        // Verify post is removed from index
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNull($indexedPost, 'Draft post should be removed from index');
    }

    /**
     * Test that publishing a draft post adds it to index
     * Requirements: 6.2
     */
    public function test_publishing_draft_post_adds_to_index(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a draft post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        // Verify post is NOT indexed
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);
        $this->assertNull($indexedPost, 'Draft post should not be indexed');

        // Publish the post
        $post->update([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Verify post is now indexed
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($indexedPost, 'Published post should be indexed');
        $this->assertEquals($post->title, $indexedPost['title']);
    }

    /**
     * Test that post deletion triggers index removal
     * Requirements: 6.3
     */
    public function test_post_deletion_triggers_index_removal(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a published post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Verify post is indexed
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);
        $this->assertNotNull($indexedPost, 'Post should be indexed before deletion');

        $postId = $post->id;

        // Delete the post
        $post->delete();

        // Verify post is removed from index
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $postId);

        $this->assertNull($indexedPost, 'Deleted post should be removed from index');
    }

    /**
     * Test that force deletion triggers index removal
     * Requirements: 6.3
     */
    public function test_post_force_deletion_triggers_index_removal(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a published post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Verify post is indexed
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);
        $this->assertNotNull($indexedPost, 'Post should be indexed before force deletion');

        $postId = $post->id;

        // Force delete the post
        $post->forceDelete();

        // Verify post is removed from index
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $postId);

        $this->assertNull($indexedPost, 'Force deleted post should be removed from index');
    }

    /**
     * Test that restored post is re-indexed
     * Requirements: 6.3
     */
    public function test_post_restoration_triggers_reindexing(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a published post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'title' => 'Post to Restore',
        ]);

        // Verify post is indexed
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);
        $this->assertNotNull($indexedPost, 'Post should be indexed');

        // Delete the post
        $post->delete();

        // Verify post is removed from index
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);
        $this->assertNull($indexedPost, 'Deleted post should be removed from index');

        // Restore the post
        $post->restore();

        // Verify post is re-indexed
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($indexedPost, 'Restored post should be re-indexed');
        $this->assertEquals('Post to Restore', $indexedPost['title']);
    }

    /**
     * Test that index includes post relationships (user, category, tags)
     * Requirements: 6.1, 6.2
     */
    public function test_index_includes_post_relationships(): void
    {
        $user = User::factory()->create(['name' => 'Test Author']);
        $category = Category::factory()->create(['name' => 'Test Category']);
        $tag1 = Tag::factory()->create(['name' => 'Tag One']);
        $tag2 = Tag::factory()->create(['name' => 'Tag Two']);

        // Create a published post with relationships
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $post->tags()->attach([$tag1->id, $tag2->id]);

        // Reload post with relationships and manually update index
        $post->load(['user', 'category', 'tags']);
        $this->searchIndexService->updatePost($post);

        // Verify index includes relationships
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($indexedPost);
        $this->assertEquals('Test Author', $indexedPost['author']);
        $this->assertEquals('Test Category', $indexedPost['category']);
        $this->assertContains('Tag One', $indexedPost['tags']);
        $this->assertContains('Tag Two', $indexedPost['tags']);
    }

    /**
     * Test that updating post relationships updates the index
     * Requirements: 6.2
     */
    public function test_updating_post_relationships_updates_index(): void
    {
        $user = User::factory()->create();
        $category1 = Category::factory()->create(['name' => 'Category One']);
        $category2 = Category::factory()->create(['name' => 'Category Two']);
        $tag1 = Tag::factory()->create(['name' => 'Tag One']);
        $tag2 = Tag::factory()->create(['name' => 'Tag Two']);

        // Create a published post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $post->tags()->attach([$tag1->id]);

        // Reload post with relationships and update index
        $post->load(['user', 'category', 'tags']);
        $this->searchIndexService->updatePost($post);

        // Verify initial index state
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);
        $this->assertEquals('Category One', $indexedPost['category']);
        $this->assertContains('Tag One', $indexedPost['tags']);
        $this->assertNotContains('Tag Two', $indexedPost['tags']);

        // Update category and tags
        $post->update(['category_id' => $category2->id]);
        $post->tags()->sync([$tag2->id]);

        // Reload post with relationships
        $post->load(['category', 'tags']);

        // Manually trigger update (since sync doesn't trigger model update event)
        $this->searchIndexService->updatePost($post);

        // Verify index is updated
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertEquals('Category Two', $indexedPost['category']);
        $this->assertContains('Tag Two', $indexedPost['tags']);
        $this->assertNotContains('Tag One', $indexedPost['tags']);
    }
}
