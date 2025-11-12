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

class PostObserverTest extends TestCase
{
    use RefreshDatabase;

    protected SearchIndexService $searchIndexService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchIndexService = app(SearchIndexService::class);
        Cache::flush();
    }

    public function test_post_created_event_indexes_published_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($indexedPost);
        $this->assertEquals($post->title, $indexedPost['title']);
    }

    public function test_post_created_event_does_not_index_draft_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNull($indexedPost);
    }

    public function test_post_updated_event_updates_index(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'title' => 'Original Title',
        ]);

        $post->update(['title' => 'Updated Title']);

        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($indexedPost);
        $this->assertEquals('Updated Title', $indexedPost['title']);
    }

    public function test_post_updated_event_removes_unpublished_post_from_index(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $post->update(['status' => 'draft']);

        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNull($indexedPost);
    }

    public function test_post_deleted_event_removes_from_index(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $postId = $post->id;
        $post->delete();

        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $postId);

        $this->assertNull($indexedPost);
    }

    public function test_post_restored_event_reindexes_published_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $post->delete();
        $post->restore();

        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($indexedPost);
        $this->assertEquals($post->title, $indexedPost['title']);
    }

    public function test_post_force_deleted_event_removes_from_index(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $postId = $post->id;
        $post->forceDelete();

        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $postId);

        $this->assertNull($indexedPost);
    }

    public function test_post_index_includes_tags_and_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Test Category']);
        $tag1 = Tag::factory()->create(['name' => 'Tag 1']);
        $tag2 = Tag::factory()->create(['name' => 'Tag 2']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Attach tags before the post is indexed (post is already created and indexed)
        // So we need to update it to re-index with tags
        $post->tags()->attach([$tag1->id, $tag2->id]);

        // Manually trigger index update with tags loaded
        $post->load(['user', 'category', 'tags']);
        $this->searchIndexService->updatePost($post);

        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($indexedPost);
        $this->assertEquals('Test Category', $indexedPost['category']);
        $this->assertContains('Tag 1', $indexedPost['tags']);
        $this->assertContains('Tag 2', $indexedPost['tags']);
    }
}
