<?php

namespace Tests\Feature;

use App\Enums\CommentStatus;
use App\Enums\PostStatus;
use App\Jobs\TrackPostView;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PostControllerShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_retrieves_post_with_eager_loading(): void
    {
        $user = User::factory()->create(['name' => 'John Doe', 'bio' => 'Author bio']);
        $category = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
        $tags = Tag::factory()->count(3)->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $post->tags()->attach($tags->pluck('id'));

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        $response->assertViewIs('posts.show');
        $response->assertViewHas('post', function ($viewPost) use ($post, $user, $category, $tags) {
            // Verify post is loaded
            $this->assertEquals($post->id, $viewPost->id);
            $this->assertEquals($post->title, $viewPost->title);

            // Verify eager loaded relationships
            $this->assertTrue($viewPost->relationLoaded('user'));
            $this->assertEquals($user->id, $viewPost->user->id);
            $this->assertEquals($user->name, $viewPost->user->name);

            $this->assertTrue($viewPost->relationLoaded('category'));
            $this->assertEquals($category->id, $viewPost->category->id);
            $this->assertEquals($category->name, $viewPost->category->name);

            $this->assertTrue($viewPost->relationLoaded('categories'));
            $this->assertTrue($viewPost->relationLoaded('tags'));
            $this->assertEquals($tags->count(), $viewPost->tags->count());

            return true;
        });
    }

    public function test_show_loads_only_published_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $publishedPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $draftPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Draft,
            'slug' => 'draft-post',
        ]);

        // Published post should be accessible
        $response = $this->get("/post/{$publishedPost->slug}");
        $response->assertStatus(200);

        // Draft post should not be accessible
        $response = $this->get("/post/{$draftPost->slug}");
        $response->assertStatus(404);
    }

    public function test_show_tracks_view_and_prevents_duplicates(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
            'view_count' => 0,
        ]);

        $initialViewCount = $post->view_count;
        $sessionId = session()->getId();

        // First view
        $response = $this->get("/post/{$post->slug}");
        $response->assertStatus(200);

        Queue::assertPushed(TrackPostView::class, function ($job) use ($post, $sessionId) {
            return $job->postId === $post->id && $job->sessionId === $sessionId;
        });

        // Process the job
        Queue::assertPushed(TrackPostView::class, 1);

        // Simulate job execution
        $this->processTrackViewJob($post, $sessionId);

        $this->assertEquals($initialViewCount + 1, $post->fresh()->view_count);

        // Second view with same session should not create duplicate
        $response = $this->get("/post/{$post->slug}");
        $response->assertStatus(200);

        // Job should still be dispatched, but won't increment count due to duplicate check
        Queue::assertPushed(TrackPostView::class, 2);

        // Simulate job execution - should not create duplicate
        $this->processTrackViewJob($post, $sessionId);

        // View count should still be the same (no duplicate)
        $this->assertEquals($initialViewCount + 1, $post->fresh()->view_count);
    }

    public function test_show_loads_related_posts_using_algorithm(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        // Create main post
        $mainPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);
        $mainPost->tags()->attach($tag->id);

        // Create related post (same category and tag)
        $relatedPost1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);
        $relatedPost1->tags()->attach($tag->id);

        // Create unrelated post (different category)
        $unrelatedPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => Category::factory()->create()->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/post/{$mainPost->slug}");

        $response->assertStatus(200);
        $response->assertViewHas('relatedPosts', function ($relatedPosts) use ($relatedPost1) {
            // Related posts should include the post with same category and tag
            $relatedPostIds = $relatedPosts->pluck('id')->toArray();
            $this->assertContains($relatedPost1->id, $relatedPostIds);

            // Should not include the main post itself
            $this->assertNotContains($mainPost->id, $relatedPostIds);

            // May or may not include unrelated post (depends on algorithm scoring)
            // But we verify the related post is included

            return true;
        });
    }

    public function test_show_loads_approved_comments_with_nesting(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        // Create top-level approved comment
        $topComment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'parent_id' => null,
            'content' => 'Top level comment',
        ]);

        // Create level 2 approved reply
        $level2Comment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'parent_id' => $topComment->id,
            'content' => 'Level 2 reply',
        ]);

        // Create level 3 approved reply
        $level3Comment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'parent_id' => $level2Comment->id,
            'content' => 'Level 3 reply',
        ]);

        // Create pending comment (should not be loaded)
        $pendingComment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
            'parent_id' => null,
            'content' => 'Pending comment',
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        $response->assertViewHas('post', function ($viewPost) use ($topComment, $pendingComment) {
            // Verify comments relationship is loaded
            $this->assertTrue($viewPost->relationLoaded('comments'));

            // Should have approved top-level comments
            $approvedComments = $viewPost->comments;
            $this->assertTrue($approvedComments->contains('id', $topComment->id));
            $this->assertFalse($approvedComments->contains('id', $pendingComment->id));

            // Verify nesting is loaded
            $firstComment = $approvedComments->first();
            if ($firstComment) {
                $this->assertTrue($firstComment->relationLoaded('replies'));
                if ($firstComment->replies->isNotEmpty()) {
                    $reply = $firstComment->replies->first();
                    $this->assertTrue($reply->relationLoaded('replies'));
                    if ($reply->replies->isNotEmpty()) {
                        $level3Reply = $reply->replies->first();
                        $this->assertTrue($level3Reply->relationLoaded('parent'));
                        $this->assertTrue($level3Reply->parent->relationLoaded('parent'));
                    }
                }
            }

            return true;
        });
    }

    public function test_show_excludes_pending_and_rejected_comments(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        // Create comments with different statuses
        $approvedComment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'parent_id' => null,
        ]);

        $pendingComment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
            'parent_id' => null,
        ]);

        $rejectedComment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => CommentStatus::Rejected,
            'parent_id' => null,
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        $response->assertViewHas('post', function ($viewPost) use ($approvedComment, $pendingComment, $rejectedComment) {
            $comments = $viewPost->comments;

            // Should only include approved comments
            $this->assertTrue($comments->contains('id', $approvedComment->id));
            $this->assertFalse($comments->contains('id', $pendingComment->id));
            $this->assertFalse($comments->contains('id', $rejectedComment->id));

            return true;
        });
    }

    public function test_show_caches_post_for_anonymous_users(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        // First request - should cache
        $response1 = $this->get("/post/{$post->slug}");
        $response1->assertStatus(200);

        // Verify cache was created
        $cacheKey = "post.{$post->slug}";
        $this->assertTrue(Cache::has($cacheKey));

        // Second request - should use cache
        $response2 = $this->get("/post/{$post->slug}");
        $response2->assertStatus(200);
        $response2->assertViewHas('post');
    }

    public function test_show_does_not_cache_for_authenticated_users(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        // Authenticated request - should not cache
        $response = $this->actingAs($user)->get("/post/{$post->slug}");
        $response->assertStatus(200);

        // Verify cache was not created (view cache, not model cache)
        $cacheKey = "post_view.{$post->slug}";
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_show_returns_404_for_nonexistent_post(): void
    {
        $response = $this->get('/post/nonexistent-post-slug');

        $response->assertStatus(404);
    }

    public function test_show_returns_404_for_unpublished_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Draft,
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(404);
    }

    public function test_show_returns_404_for_future_published_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->addDay(), // Future date
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(404);
    }

    public function test_show_loads_comments_in_correct_order(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        // Create comments at different times
        $oldComment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'parent_id' => null,
            'created_at' => now()->subDays(3),
        ]);

        $newComment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'parent_id' => null,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        $response->assertViewHas('post', function ($viewPost) use ($newComment, $oldComment) {
            $comments = $viewPost->comments;

            // Top-level comments should be ordered by created_at desc (newest first)
            $this->assertEquals($newComment->id, $comments->first()->id);
            $this->assertEquals($oldComment->id, $comments->last()->id);

            return true;
        });
    }

    /**
     * Helper method to simulate job execution for testing
     */
    protected function processTrackViewJob(Post $post, string $sessionId): void
    {
        $job = new TrackPostView(
            $post->id,
            $sessionId,
            '127.0.0.1',
            'PHPUnit',
            null
        );

        $job->handle();
    }
}

