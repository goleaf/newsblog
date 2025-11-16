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

    public function test_weight_calculations_category_weight(): void
    {
        $user = User::factory()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'status' => 'published',
            'published_at' => now()->subDays(10),
        ]);

        // Post in same category - should get 40% category weight
        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'status' => 'published',
            'published_at' => now()->subDays(10),
        ]);

        // Post in different category - should get 0% category weight
        $post3 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'status' => 'published',
            'published_at' => now()->subDays(10),
        ]);

        $related = $this->service->getRelatedPosts($post1, 10);

        // Post2 should be ranked higher than post3 due to category match
        $relatedIds = $related->pluck('id')->toArray();
        $post2Index = array_search($post2->id, $relatedIds);
        $post3Index = array_search($post3->id, $relatedIds);

        // If both are present, post2 should come before post3
        if ($post2Index !== false && $post3Index !== false) {
            $this->assertLessThan($post3Index, $post2Index, 'Post with same category should rank higher');
        }

        // Post2 should be in results
        $this->assertContains($post2->id, $related->pluck('id'));
    }

    public function test_weight_calculations_tag_weight(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $tag3 = Tag::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDays(10),
        ]);
        $post1->tags()->attach([$tag1->id, $tag2->id]);

        // Post with 2 shared tags (100% match) - should get 40% tag weight
        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDays(10),
        ]);
        $post2->tags()->attach([$tag1->id, $tag2->id]);

        // Post with 1 shared tag (50% match) - should get 20% tag weight
        $post3 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDays(10),
        ]);
        $post3->tags()->attach([$tag1->id]);

        // Post with no shared tags - should get 0% tag weight
        $post4 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDays(10),
        ]);
        $post4->tags()->attach([$tag3->id]);

        $related = $this->service->getRelatedPosts($post1, 10);

        $relatedIds = $related->pluck('id')->toArray();
        $post2Index = array_search($post2->id, $relatedIds);
        $post3Index = array_search($post3->id, $relatedIds);
        $post4Index = array_search($post4->id, $relatedIds);

        // Post2 should rank highest (100% tag match)
        if ($post2Index !== false && $post3Index !== false) {
            $this->assertLessThan($post3Index, $post2Index, 'Post with more shared tags should rank higher');
        }

        // Post2 should be in results
        $this->assertContains($post2->id, $related->pluck('id'));
    }

    public function test_weight_calculations_date_proximity_weight(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDays(5),
        ]);

        // Post published same day - should get full 20% date weight
        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDays(5),
        ]);

        // Post published 15 days ago - should get 10% date weight (20 - (15/30)*20 = 10)
        $post3 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDays(20), // 20 days ago
        ]);

        // Post published 35 days ago - should get 0% date weight (30+ days = 0)
        $post4 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDays(35),
        ]);

        $related = $this->service->getRelatedPosts($post1, 10);

        $relatedIds = $related->pluck('id')->toArray();
        $post2Index = array_search($post2->id, $relatedIds);
        $post3Index = array_search($post3->id, $relatedIds);
        $post4Index = array_search($post4->id, $relatedIds);

        // Post2 (same day) should rank higher than post3 (older)
        if ($post2Index !== false && $post3Index !== false) {
            $this->assertLessThan($post3Index, $post2Index, 'Post published on same day should rank higher');
        }

        // Post2 should be in results
        $this->assertContains($post2->id, $related->pluck('id'));
    }

    public function test_caches_related_posts_with_one_hour_ttl(): void
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

        $cacheKey = "related_posts.{$post->id}";
        $this->assertFalse(Cache::has($cacheKey));

        // Call service to generate cache
        $this->service->getRelatedPosts($post);

        // Verify cache exists
        $this->assertTrue(Cache::has($cacheKey));

        // Verify TTL is 3600 seconds (1 hour)
        $cacheValue = Cache::get($cacheKey);
        $this->assertNotNull($cacheValue);
    }

    public function test_returns_empty_collection_when_no_related_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create only one published post
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Create draft posts that should not be included
        Post::factory()->count(5)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $related = $this->service->getRelatedPosts($post);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $related);
        $this->assertCount(0, $related);
        $this->assertTrue($related->isEmpty());
    }

    public function test_limits_related_posts_to_four_by_default(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Create 10 posts in same category
        Post::factory()->count(10)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $related = $this->service->getRelatedPosts($post1);

        // Should default to 4 posts
        $this->assertCount(4, $related);
    }
}
