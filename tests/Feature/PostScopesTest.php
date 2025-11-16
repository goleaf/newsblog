<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_published_filters_published_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create published post
        $publishedPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        // Create draft post
        $draftPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Draft,
        ]);

        // Create scheduled post
        $scheduledPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Scheduled,
            'scheduled_at' => now()->addDay(),
        ]);

        // Create published post with future published_at
        $futurePost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $publishedPosts = Post::published()->get();

        $this->assertCount(1, $publishedPosts);
        $this->assertTrue($publishedPosts->contains($publishedPost));
        $this->assertFalse($publishedPosts->contains($draftPost));
        $this->assertFalse($publishedPosts->contains($scheduledPost));
        $this->assertFalse($publishedPosts->contains($futurePost));
    }

    public function test_scope_featured_filters_featured_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create featured post
        $featuredPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_featured' => true,
        ]);

        // Create non-featured post
        $regularPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_featured' => false,
        ]);

        $featuredPosts = Post::featured()->get();

        $this->assertCount(1, $featuredPosts);
        $this->assertTrue($featuredPosts->contains($featuredPost));
        $this->assertFalse($featuredPosts->contains($regularPost));
    }

    public function test_scope_breaking_filters_breaking_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create breaking post
        $breakingPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_breaking' => true,
        ]);

        // Create non-breaking post
        $regularPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_breaking' => false,
        ]);

        $breakingPosts = Post::breaking()->get();

        $this->assertCount(1, $breakingPosts);
        $this->assertTrue($breakingPosts->contains($breakingPost));
        $this->assertFalse($breakingPosts->contains($regularPost));
    }

    public function test_scope_scheduled_filters_scheduled_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create scheduled post with future date
        $scheduledPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Scheduled,
            'scheduled_at' => now()->addDay(),
        ]);

        // Create scheduled post with past date (should not be included)
        $pastScheduledPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Scheduled,
            'scheduled_at' => now()->subDay(),
        ]);

        // Create published post
        $publishedPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $scheduledPosts = Post::scheduled()->get();

        $this->assertCount(1, $scheduledPosts);
        $this->assertTrue($scheduledPosts->contains($scheduledPost));
        $this->assertFalse($scheduledPosts->contains($pastScheduledPost));
        $this->assertFalse($scheduledPosts->contains($publishedPost));
    }

    public function test_scope_popular_orders_by_view_count(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create posts with different view counts
        $lowViewsPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'view_count' => 10,
        ]);

        $mediumViewsPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'view_count' => 50,
        ]);

        $highViewsPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'view_count' => 100,
        ]);

        $popularPosts = Post::popular()->get();

        $this->assertCount(3, $popularPosts);
        $this->assertEquals($highViewsPost->id, $popularPosts->first()->id);
        $this->assertEquals($mediumViewsPost->id, $popularPosts->get(1)->id);
        $this->assertEquals($lowViewsPost->id, $popularPosts->last()->id);
    }

    public function test_scopes_can_be_combined(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create published featured post
        $publishedFeaturedPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
            'is_featured' => true,
            'view_count' => 100,
        ]);

        // Create published non-featured post
        $publishedRegularPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
            'is_featured' => false,
            'view_count' => 50,
        ]);

        // Create draft featured post
        $draftFeaturedPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Draft,
            'is_featured' => true,
        ]);

        $results = Post::published()->featured()->popular()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($publishedFeaturedPost));
        $this->assertFalse($results->contains($publishedRegularPost));
        $this->assertFalse($results->contains($draftFeaturedPost));
    }
}
