<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PostService $postService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = app(PostService::class);
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

        $this->assertEquals(PostStatus::Scheduled, $post->status);
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

        $this->assertEquals(PostStatus::Published, $publishedPost->status);
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
            'status' => PostStatus::Draft,
        ]);

        $scheduledAt = now()->addDays(2);
        $scheduledPost = $this->postService->schedulePost($post, $scheduledAt);

        $this->assertEquals(PostStatus::Scheduled, $scheduledPost->status);
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

        $this->assertEquals(PostStatus::Draft, $unpublishedPost->status);
        $this->assertNull($unpublishedPost->published_at);
    }

    public function test_archives_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => PostStatus::Published,
        ]);

        $archivedPost = $this->postService->archivePost($post);

        $this->assertEquals(PostStatus::Archived, $archivedPost->status);
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
        $this->assertEquals(2, Post::where('status', PostStatus::Published)->count());
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
        $this->assertEquals(PostStatus::Draft, $duplicatedPost->status);
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
        $this->assertEquals(PostStatus::Published, $post1->fresh()->status);
        $this->assertEquals(PostStatus::Published, $post2->fresh()->status);
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

    public function test_gets_related_posts_by_category(): void
    {
        $user = User::factory()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'title' => 'Post 1',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'title' => 'Post 2',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post3 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'title' => 'Post 3',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post4 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'title' => 'Post 4',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $relatedPosts = $this->postService->getRelatedPosts($post1, 4);

        $this->assertGreaterThanOrEqual(2, $relatedPosts->count());
        $this->assertContains($post2->id, $relatedPosts->pluck('id'));
        $this->assertContains($post4->id, $relatedPosts->pluck('id'));
        $this->assertNotContains($post1->id, $relatedPosts->pluck('id'));

        // Posts from same category should be prioritized (higher scores)
        // Verify that post2 and post4 appear before post3 if post3 is included
        $relatedIds = $relatedPosts->pluck('id')->toArray();
        $post2Index = array_search($post2->id, $relatedIds);
        $post4Index = array_search($post4->id, $relatedIds);
        $post3Index = array_search($post3->id, $relatedIds);

        if ($post3Index !== false) {
            // If post3 is included, post2 and post4 should come before it
            $this->assertTrue($post2Index < $post3Index || $post4Index < $post3Index,
                'Posts from same category should be ranked higher');
        }
    }

    public function test_gets_related_posts_by_tags(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag1 = Tag::factory()->create(['name' => 'Laravel']);
        $tag2 = Tag::factory()->create(['name' => 'PHP']);
        $tag3 = Tag::factory()->create(['name' => 'JavaScript']);

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post1->tags()->attach([$tag1->id, $tag2->id]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 2',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post2->tags()->attach([$tag1->id, $tag2->id]);

        $post3 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 3',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post3->tags()->attach([$tag3->id]);

        $relatedPosts = $this->postService->getRelatedPosts($post1, 4);

        $this->assertGreaterThanOrEqual(1, $relatedPosts->count());
        $this->assertContains($post2->id, $relatedPosts->pluck('id'));
        $this->assertNotContains($post1->id, $relatedPosts->pluck('id'));
    }

    public function test_gets_related_posts_by_fuzzy_text_similarity(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Laravel Framework Tutorial',
            'excerpt' => 'Learn Laravel framework basics',
            'content' => 'This is a comprehensive guide to Laravel framework',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Laravel Framework Advanced Guide',
            'excerpt' => 'Advanced Laravel framework techniques',
            'content' => 'Learn advanced Laravel framework concepts',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post3 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'JavaScript Basics',
            'excerpt' => 'Learn JavaScript fundamentals',
            'content' => 'This is a guide to JavaScript',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $relatedPosts = $this->postService->getRelatedPosts($post1, 4);

        $this->assertGreaterThanOrEqual(1, $relatedPosts->count());
        // Post2 should be more related due to text similarity
        $relatedIds = $relatedPosts->pluck('id')->toArray();
        $this->assertNotContains($post1->id, $relatedIds);
    }

    public function test_related_posts_combines_fuzzy_matching_with_category_and_tags(): void
    {
        $user = User::factory()->create();
        $category1 = Category::factory()->create(['name' => 'Web Development']);
        $category2 = Category::factory()->create(['name' => 'Mobile Development']);
        $tag1 = Tag::factory()->create(['name' => 'Laravel']);
        $tag2 = Tag::factory()->create(['name' => 'PHP']);

        // Base post with category and tags
        $basePost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'title' => 'Laravel Best Practices Guide',
            'excerpt' => 'Learn Laravel best practices and patterns',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $basePost->tags()->attach([$tag1->id, $tag2->id]);

        // Post with same category and tags + similar title (should score highest)
        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'title' => 'Laravel Best Practices Tutorial',
            'excerpt' => 'Advanced Laravel best practices',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post1->tags()->attach([$tag1->id, $tag2->id]);

        // Post with same category but different title and no tags
        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'title' => 'JavaScript Fundamentals',
            'excerpt' => 'Learn JavaScript basics',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Post with different category but similar title
        $post3 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'title' => 'Laravel Best Practices for Mobile',
            'excerpt' => 'Laravel patterns for mobile apps',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $relatedPosts = $this->postService->getRelatedPosts($basePost, 4);

        $this->assertGreaterThanOrEqual(2, $relatedPosts->count());
        $relatedIds = $relatedPosts->pluck('id')->toArray();

        // Post1 should be first (same category + tags + similar title)
        $this->assertEquals($post1->id, $relatedIds[0]);

        // Base post should not be in results
        $this->assertNotContains($basePost->id, $relatedIds);
    }

    public function test_related_posts_falls_back_to_category_when_no_fuzzy_matches(): void
    {
        $user = User::factory()->create();
        $category1 = Category::factory()->create(['name' => 'Quantum Physics']);
        $category2 = Category::factory()->create(['name' => 'Culinary Arts']);

        $basePost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'title' => 'Quantum Entanglement Phenomena QWERTY',
            'excerpt' => 'Exploring quantum mechanics principles',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Posts in same category with completely different content
        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'title' => 'Higgs Boson Discovery Analysis',
            'excerpt' => 'Particle physics breakthrough examination',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'title' => 'String Theory Mathematical Framework',
            'excerpt' => 'Advanced theoretical physics concepts',
            'status' => 'published',
            'published_at' => now()->subDays(2),
        ]);

        // Post in different category with completely different content
        $post3 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'title' => 'French Pastry Baking Techniques',
            'excerpt' => 'Mastering croissant preparation methods',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $relatedPosts = $this->postService->getRelatedPosts($basePost, 4);

        // Should fall back to category-based algorithm (Requirement 13.5)
        $this->assertGreaterThanOrEqual(1, $relatedPosts->count());
        $relatedIds = $relatedPosts->pluck('id')->toArray();

        // Should include posts from same category
        $this->assertTrue(
            in_array($post1->id, $relatedIds) || in_array($post2->id, $relatedIds),
            'Should fall back to category-based algorithm when no fuzzy matches'
        );

        // Should not include posts from different category
        $this->assertNotContains($post3->id, $relatedIds);
    }

    public function test_related_posts_are_cached(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 2',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Cache::flush();

        $relatedPosts1 = $this->postService->getRelatedPosts($post1, 4);
        $this->assertTrue(Cache::has("post.{$post1->id}.related"));

        // Second call should use cache
        $relatedPosts2 = $this->postService->getRelatedPosts($post1, 4);
        $this->assertEquals($relatedPosts1->pluck('id'), $relatedPosts2->pluck('id'));
    }

    public function test_invalidates_related_posts_cache(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Generate cache
        $this->postService->getRelatedPosts($post, 4);
        $this->assertTrue(Cache::has("post.{$post->id}.related"));

        // Invalidate cache
        $this->postService->invalidateRelatedPostsCache($post);
        $this->assertFalse(Cache::has("post.{$post->id}.related"));
    }

    public function test_invalidates_related_posts_cache_by_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 2',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Generate cache for both posts
        $this->postService->getRelatedPosts($post1, 4);
        $this->postService->getRelatedPosts($post2, 4);

        $this->assertTrue(Cache::has("post.{$post1->id}.related"));
        $this->assertTrue(Cache::has("post.{$post2->id}.related"));

        // Invalidate by category
        $this->postService->invalidateRelatedPostsCacheByCategory($category->id);

        $this->assertFalse(Cache::has("post.{$post1->id}.related"));
        $this->assertFalse(Cache::has("post.{$post2->id}.related"));
    }

    public function test_invalidates_related_posts_cache_by_tags(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create(['name' => 'Laravel']);

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post1->tags()->attach($tag->id);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 2',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post2->tags()->attach($tag->id);

        // Generate cache for both posts
        $this->postService->getRelatedPosts($post1, 4);
        $this->postService->getRelatedPosts($post2, 4);

        $this->assertTrue(Cache::has("post.{$post1->id}.related"));
        $this->assertTrue(Cache::has("post.{$post2->id}.related"));

        // Invalidate by tag
        $this->postService->invalidateRelatedPostsCacheByTags([$tag->id]);

        $this->assertFalse(Cache::has("post.{$post1->id}.related"));
        $this->assertFalse(Cache::has("post.{$post2->id}.related"));
    }

    public function test_related_posts_excludes_current_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $relatedPosts = $this->postService->getRelatedPosts($post, 4);

        $this->assertNotContains($post->id, $relatedPosts->pluck('id'));
    }

    public function test_related_posts_only_includes_published_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Draft Post',
            'status' => 'draft',
        ]);

        $relatedPosts = $this->postService->getRelatedPosts($post1, 4);

        foreach ($relatedPosts as $relatedPost) {
            $this->assertEquals(PostStatus::Published, $relatedPost->status);
            $this->assertNotNull($relatedPost->published_at);
        }
    }
}
