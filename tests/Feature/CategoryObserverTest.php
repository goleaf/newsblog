<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\SearchIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CategoryObserverTest extends TestCase
{
    use RefreshDatabase;

    protected SearchIndexService $searchIndexService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchIndexService = app(SearchIndexService::class);
        Cache::flush();
    }

    public function test_category_updated_event_updates_related_posts_index(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Original Category']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Update category name
        $category->update(['name' => 'Updated Category']);

        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($indexedPost);
        $this->assertEquals('Updated Category', $indexedPost['category']);
    }

    public function test_category_updated_event_only_updates_when_name_slug_or_description_changes(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'description' => 'Original Description',
        ]);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Update category description
        $category->update(['description' => 'Updated Description']);

        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($indexedPost);
        $this->assertEquals('Test Category', $indexedPost['category']);
    }

    public function test_category_deleting_event_updates_related_posts_index(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Category To Delete']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $postId = $post->id;

        // Delete category - this will cascade delete the post due to foreign key constraint
        $category->delete();

        // Post should be removed from index because it was cascade deleted
        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $postId);

        $this->assertNull($indexedPost);
    }

    public function test_category_updated_event_only_affects_published_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Original Category']);

        $publishedPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $draftPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        // Update category name
        $category->update(['name' => 'Updated Category']);

        $index = $this->searchIndexService->getIndex('posts');
        $indexedPublishedPost = collect($index)->firstWhere('id', $publishedPost->id);
        $indexedDraftPost = collect($index)->firstWhere('id', $draftPost->id);

        $this->assertNotNull($indexedPublishedPost);
        $this->assertEquals('Updated Category', $indexedPublishedPost['category']);
        $this->assertNull($indexedDraftPost);
    }

    public function test_category_slug_update_updates_related_posts_index(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Test Category', 'slug' => 'original-slug']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Update category slug
        $category->update(['slug' => 'updated-slug']);

        $index = $this->searchIndexService->getIndex('posts');
        $indexedPost = collect($index)->firstWhere('id', $post->id);

        $this->assertNotNull($indexedPost);
        $this->assertEquals('Test Category', $indexedPost['category']);
    }
}
