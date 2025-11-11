<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PostService $postService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = new PostService;
    }

    public function test_generates_unique_slug_from_title(): void
    {
        $slug = $this->postService->generateUniqueSlug('Test Post Title');

        $this->assertEquals('test-post-title', $slug);
    }

    public function test_generates_unique_slug_with_counter_when_duplicate_exists(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Post',
            'slug' => 'test-post',
        ]);

        $slug = $this->postService->generateUniqueSlug('Test Post');

        $this->assertEquals('test-post-1', $slug);
    }

    public function test_generates_unique_slug_with_incremented_counter(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'slug' => 'test-post',
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'slug' => 'test-post-1',
        ]);

        $slug = $this->postService->generateUniqueSlug('Test Post');

        $this->assertEquals('test-post-2', $slug);
    }

    public function test_calculates_reading_time_correctly(): void
    {
        $content = str_repeat('word ', 200); // 200 words = 1 minute
        $readingTime = $this->postService->calculateReadingTime($content);

        $this->assertEquals(1, $readingTime);
    }

    public function test_calculates_reading_time_rounds_up(): void
    {
        $content = str_repeat('word ', 250); // 250 words = 1.25 minutes, should round to 2
        $readingTime = $this->postService->calculateReadingTime($content);

        $this->assertEquals(2, $readingTime);
    }

    public function test_calculates_reading_time_strips_html_tags(): void
    {
        $content = '<p>'.str_repeat('word ', 200).'</p><div>extra</div>';
        $readingTime = $this->postService->calculateReadingTime($content);

        $this->assertEquals(2, $readingTime); // 201 words
    }

    public function test_creates_post_with_automatic_slug_generation(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'My New Post',
            'content' => 'Post content here',
            'status' => 'draft',
        ]);

        $this->assertEquals('my-new-post', $post->slug);
        $this->assertDatabaseHas('posts', [
            'title' => 'My New Post',
            'slug' => 'my-new-post',
        ]);
    }

    public function test_creates_post_with_automatic_reading_time_calculation(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $content = str_repeat('word ', 400); // 400 words = 2 minutes

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Reading Time Test',
            'content' => $content,
            'status' => 'draft',
        ]);

        $this->assertEquals(2, $post->reading_time);
    }

    public function test_updates_post_slug_when_title_changes(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Original Title',
            'slug' => 'original-title',
        ]);

        $updatedPost = $this->postService->updatePost($post, [
            'title' => 'Updated Title',
        ]);

        $this->assertEquals('updated-title', $updatedPost->slug);
    }

    public function test_updates_reading_time_when_content_changes(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'content' => str_repeat('word ', 200),
            'reading_time' => 1,
        ]);

        $newContent = str_repeat('word ', 600); // 600 words = 3 minutes

        $updatedPost = $this->postService->updatePost($post, [
            'content' => $newContent,
        ]);

        $this->assertEquals(3, $updatedPost->reading_time);
    }

    public function test_schedules_post_for_future_publication(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $scheduledAt = now()->addDay();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Scheduled Post',
            'content' => 'Content',
            'scheduled_at' => $scheduledAt,
        ]);

        $this->assertEquals('scheduled', $post->status);
        $this->assertNotNull($post->scheduled_at);
        $this->assertNull($post->published_at);
    }

    public function test_publishes_post_immediately(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $publishedPost = $this->postService->publishPost($post);

        $this->assertEquals('published', $publishedPost->status);
        $this->assertNotNull($publishedPost->published_at);
        $this->assertNull($publishedPost->scheduled_at);
    }

    public function test_schedule_post_method_sets_correct_status(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $scheduledAt = now()->addDays(2);
        $scheduledPost = $this->postService->schedulePost($post, $scheduledAt);

        $this->assertEquals('scheduled', $scheduledPost->status);
        $this->assertNotNull($scheduledPost->scheduled_at);
        $this->assertNull($scheduledPost->published_at);
    }

    public function test_schedule_post_throws_exception_for_past_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->postService->schedulePost($post, now()->subDay());
    }

    public function test_unpublishes_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $unpublishedPost = $this->postService->unpublishPost($post);

        $this->assertEquals('draft', $unpublishedPost->status);
        $this->assertNull($unpublishedPost->published_at);
    }

    public function test_archives_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $archivedPost = $this->postService->archivePost($post);

        $this->assertEquals('archived', $archivedPost->status);
    }

    public function test_gets_posts_ready_to_publish(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a scheduled post that's ready
        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);

        // Create a scheduled post that's not ready yet
        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $readyPosts = $this->postService->getPostsReadyToPublish();

        $this->assertCount(1, $readyPosts);
    }

    public function test_publishes_scheduled_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->subHour(),
        ]);

        $count = $this->postService->publishScheduledPosts();

        $this->assertEquals(2, $count);
        $this->assertEquals(2, Post::where('status', 'published')->count());
    }

    public function test_duplicates_post_with_unique_slug(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $originalPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Original Post',
            'slug' => 'original-post',
            'status' => 'published',
            'view_count' => 100,
        ]);

        $duplicatedPost = $this->postService->duplicatePost($originalPost);

        $this->assertEquals('Original Post (Copy)', $duplicatedPost->title);
        $this->assertEquals('original-post-copy', $duplicatedPost->slug);
        $this->assertEquals('draft', $duplicatedPost->status);
        $this->assertEquals(0, $duplicatedPost->view_count);
        $this->assertNull($duplicatedPost->published_at);
    }

    public function test_bulk_updates_post_status(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $count = $this->postService->bulkUpdateStatus([$post1->id, $post2->id], 'published');

        $this->assertEquals(2, $count);
        $this->assertEquals('published', $post1->fresh()->status);
        $this->assertEquals('published', $post2->fresh()->status);
    }

    public function test_gets_post_statistics(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'content' => str_repeat('word ', 400),
            'view_count' => 50,
        ]);

        $stats = $this->postService->getPostStatistics($post);

        $this->assertEquals(50, $stats['view_count']);
        $this->assertEquals(400, $stats['word_count']);
        $this->assertArrayHasKey('comment_count', $stats);
        $this->assertArrayHasKey('bookmark_count', $stats);
        $this->assertArrayHasKey('reaction_count', $stats);
    }
}
