<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\RelatedPostsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RelatedPostsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RelatedPostsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RelatedPostsService::class);
    }

    public function test_finds_related_posts_by_same_category(): void
    {
        $user = User::factory()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post3 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $related = $this->service->getRelatedPosts($post1);

        $this->assertContains($post2->id, $related->pluck('id'));
        $this->assertNotContains($post1->id, $related->pluck('id'));
    }

    public function test_finds_related_posts_by_shared_tags(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post1->tags()->attach([$tag1->id, $tag2->id]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post2->tags()->attach([$tag1->id]);

        $related = $this->service->getRelatedPosts($post1);

        $this->assertContains($post2->id, $related->pluck('id'));
    }

    public function test_limits_related_posts_to_specified_count(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->count(10)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $related = $this->service->getRelatedPosts($post1, 4);

        $this->assertCount(4, $related);
    }

    public function test_caches_related_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Cache::flush();

        $this->service->getRelatedPosts($post);

        $this->assertTrue(Cache::has("related_posts.{$post->id}"));
    }

    public function test_invalidates_cache_for_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->service->getRelatedPosts($post);
        $this->assertTrue(Cache::has("related_posts.{$post->id}"));

        $this->service->invalidateCache($post);
        $this->assertFalse(Cache::has("related_posts.{$post->id}"));
    }

    public function test_excludes_current_post_from_results(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $related = $this->service->getRelatedPosts($post);

        $this->assertNotContains($post->id, $related->pluck('id'));
    }

    public function test_only_returns_published_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $related = $this->service->getRelatedPosts($post1);

        $this->assertGreaterThan(0, $related->count());

        foreach ($related as $relatedPost) {
            $this->assertEquals('published', $relatedPost->status);
        }
    }
}
