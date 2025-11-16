<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PostManagementTest extends TestCase
{
    use RefreshDatabase;

    protected PostService $postService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = app(PostService::class);
    }

    /**
     * Test post creation with relationships
     */
    public function test_creates_post_with_user_and_category_relationship(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Post with Relationships',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals($user->id, $post->user_id);
        $this->assertEquals($category->id, $post->category_id);
        $this->assertTrue($post->user->is($user));
        $this->assertTrue($post->category->is($category));
    }

    public function test_creates_post_with_tags_relationship(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag1 = Tag::factory()->create(['name' => 'PHP']);
        $tag2 = Tag::factory()->create(['name' => 'Laravel']);
        $tag3 = Tag::factory()->create(['name' => 'Testing']);

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Post with Tags',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $post->tags()->attach([$tag1->id, $tag2->id, $tag3->id]);
        $post->refresh();
        $post->load('tags');

        $this->assertCount(3, $post->tags);
        $this->assertTrue($post->tags->contains($tag1));
        $this->assertTrue($post->tags->contains($tag2));
        $this->assertTrue($post->tags->contains($tag3));
    }

    public function test_creates_post_with_multiple_categories(): void
    {
        $user = User::factory()->create();
        $primaryCategory = Category::factory()->create(['name' => 'Primary Category']);
        $secondaryCategory1 = Category::factory()->create(['name' => 'Secondary Category 1']);
        $secondaryCategory2 = Category::factory()->create(['name' => 'Secondary Category 2']);

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $primaryCategory->id,
            'title' => 'Test Post with Multiple Categories',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $post->categories()->attach([$secondaryCategory1->id, $secondaryCategory2->id]);

        $this->assertEquals($primaryCategory->id, $post->category_id);
        $this->assertCount(2, $post->categories);
        $this->assertTrue($post->categories->contains($secondaryCategory1));
        $this->assertTrue($post->categories->contains($secondaryCategory2));
    }

    /**
     * Test slug generation and uniqueness
     */
    public function test_generates_slug_from_title_on_creation(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'My Amazing Blog Post Title',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $this->assertEquals('my-amazing-blog-post-title', $post->slug);
    }

    public function test_generates_unique_slug_when_duplicate_exists(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create first post
        $firstPost = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Duplicate Title',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        // Create second post with same title
        $secondPost = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Duplicate Title',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $this->assertEquals('duplicate-title', $firstPost->slug);
        $this->assertEquals('duplicate-title-1', $secondPost->slug);
        $this->assertNotEquals($firstPost->slug, $secondPost->slug);
    }

    public function test_generates_unique_slug_with_incremented_counter(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create multiple posts with same title
        $post1 = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Same Title',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $post2 = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Same Title',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $post3 = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Same Title',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $this->assertEquals('same-title', $post1->slug);
        $this->assertEquals('same-title-1', $post2->slug);
        $this->assertEquals('same-title-2', $post3->slug);
    }

    public function test_updates_slug_when_title_changes(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Original Title',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $this->assertEquals('original-title', $post->slug);

        $updatedPost = $this->postService->updatePost($post, [
            'title' => 'Updated Title',
        ]);

        $this->assertEquals('updated-title', $updatedPost->slug);
    }

    public function test_preserves_custom_slug_when_provided(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'My Post Title',
            'slug' => 'custom-slug-name',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $this->assertStringStartsWith('custom-slug-name', $post->slug);
    }

    /**
     * Test reading time calculation
     */
    public function test_calculates_reading_time_on_creation(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $content = str_repeat('word ', 400); // 400 words = 2 minutes

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Reading Time Test',
            'content' => $content,
            'status' => PostStatus::Draft,
        ]);

        $this->assertEquals(2, $post->reading_time);
    }

    public function test_calculates_reading_time_rounds_up(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $content = str_repeat('word ', 250); // 250 words = 1.25 minutes, rounds to 2

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Reading Time Rounding Test',
            'content' => $content,
            'status' => PostStatus::Draft,
        ]);

        $this->assertEquals(2, $post->reading_time);
    }

    public function test_calculates_reading_time_strips_html_tags(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $content = '<p>'.str_repeat('word ', 200).'</p><div>'.str_repeat('word ', 50).'</div>'; // 250 words

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'HTML Reading Time Test',
            'content' => $content,
            'status' => PostStatus::Draft,
        ]);

        $this->assertEquals(2, $post->reading_time);
    }

    public function test_recalculates_reading_time_when_content_changes(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Reading Time Update Test',
            'content' => str_repeat('word ', 200), // 200 words = 1 minute
            'status' => PostStatus::Draft,
        ]);

        $this->assertEquals(1, $post->reading_time);

        $updatedPost = $this->postService->updatePost($post, [
            'content' => str_repeat('word ', 600), // 600 words = 3 minutes
        ]);

        $this->assertEquals(3, $updatedPost->reading_time);
    }

    /**
     * Test post publishing workflow
     */
    public function test_creates_draft_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Draft Post',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $this->assertEquals(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
        $this->assertNull($post->scheduled_at);
    }

    public function test_publishes_draft_post(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Draft Post to Publish',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $this->assertEquals(PostStatus::Draft, $post->status);

        $publishedPost = $this->postService->publishPost($post);

        $this->assertEquals(PostStatus::Published, $publishedPost->status);
        $this->assertNotNull($publishedPost->published_at);
        $this->assertNull($publishedPost->scheduled_at);
        $this->assertTrue($publishedPost->published_at->isPast());
    }

    public function test_schedules_post_for_future_publication(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post to Schedule',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $scheduledAt = now()->addDays(2);
        $scheduledPost = $this->postService->schedulePost($post, $scheduledAt);

        $this->assertEquals(PostStatus::Scheduled, $scheduledPost->status);
        $this->assertNotNull($scheduledPost->scheduled_at);
        $this->assertNull($scheduledPost->published_at);
        $this->assertTrue($scheduledPost->scheduled_at->isFuture());
    }

    public function test_schedule_post_throws_exception_for_past_date(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post to Schedule',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $pastDate = now()->subDay();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Scheduled time must be in the future.');

        $this->postService->schedulePost($post, $pastDate);
    }

    public function test_unpublishes_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post to Unpublish',
            'content' => 'Post content',
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $unpublishedPost = $this->postService->unpublishPost($post);

        $this->assertEquals(PostStatus::Draft, $unpublishedPost->status);
        $this->assertNull($unpublishedPost->published_at);
        $this->assertNull($unpublishedPost->scheduled_at);
    }

    public function test_archives_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post to Archive',
            'content' => 'Post content',
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $archivedPost = $this->postService->archivePost($post);

        $this->assertEquals(PostStatus::Archived, $archivedPost->status);
    }

    public function test_complete_publishing_workflow(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        // 1. Create draft post
        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Complete Workflow Post',
            'content' => str_repeat('word ', 400),
            'status' => PostStatus::Draft,
        ]);

        $post->tags()->attach($tag->id);
        $post->refresh();
        $post->load('tags');

        $this->assertEquals(PostStatus::Draft, $post->status);
        $this->assertEquals('complete-workflow-post', $post->slug);
        $this->assertEquals(2, $post->reading_time);
        $this->assertCount(1, $post->tags);

        // 2. Schedule post
        $scheduledAt = now()->addDay();
        $scheduledPost = $this->postService->schedulePost($post, $scheduledAt);

        $this->assertEquals(PostStatus::Scheduled, $scheduledPost->status);
        $this->assertNotNull($scheduledPost->scheduled_at);

        // 3. Publish post (overrides schedule)
        $publishedPost = $this->postService->publishPost($scheduledPost);

        $this->assertEquals(PostStatus::Published, $publishedPost->status);
        $this->assertNotNull($publishedPost->published_at);
        $this->assertNull($publishedPost->scheduled_at);

        // 4. Verify relationships are intact
        $publishedPost->load(['user', 'category', 'tags']);
        $this->assertTrue($publishedPost->user->is($user));
        $this->assertTrue($publishedPost->category->is($category));
        $this->assertCount(1, $publishedPost->tags);
    }

    public function test_publishing_workflow_with_status_transitions(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Start as draft
        $post = $this->postService->createPost([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Status Transition Post',
            'content' => 'Post content',
            'status' => PostStatus::Draft,
        ]);

        $this->assertEquals(PostStatus::Draft, $post->status);

        // Draft -> Scheduled
        $scheduledPost = $this->postService->schedulePost($post, now()->addDay());
        $this->assertEquals(PostStatus::Scheduled, $scheduledPost->status);

        // Scheduled -> Published
        $publishedPost = $this->postService->publishPost($scheduledPost);
        $this->assertEquals(PostStatus::Published, $publishedPost->status);

        // Published -> Draft (unpublish)
        $unpublishedPost = $this->postService->unpublishPost($publishedPost);
        $this->assertEquals(PostStatus::Draft, $unpublishedPost->status);

        // Draft -> Published
        $rePublishedPost = $this->postService->publishPost($unpublishedPost);
        $this->assertEquals(PostStatus::Published, $rePublishedPost->status);

        // Published -> Archived
        $archivedPost = $this->postService->archivePost($rePublishedPost);
        $this->assertEquals(PostStatus::Archived, $archivedPost->status);
    }
}
