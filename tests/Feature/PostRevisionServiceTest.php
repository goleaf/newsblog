<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use App\Services\PostRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostRevisionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PostRevisionService $revisionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->revisionService = new PostRevisionService;
    }

    public function test_creates_revision_from_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Original Title',
            'content' => 'Original content',
            'excerpt' => 'Original excerpt',
        ]);

        $this->actingAs($user);

        $revision = $this->revisionService->createRevision($post);

        $this->assertInstanceOf(PostRevision::class, $revision);
        $this->assertEquals($post->id, $revision->post_id);
        $this->assertEquals($user->id, $revision->user_id);
        $this->assertEquals('Original Title', $revision->title);
        $this->assertEquals('Original content', $revision->content);
        $this->assertEquals('Original excerpt', $revision->excerpt);
    }

    public function test_creates_revision_with_note(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user);

        $revision = $this->revisionService->createRevision($post, 'Test note');

        $this->assertEquals('Test note', $revision->revision_note);
    }

    public function test_enforces_revision_limit(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user);

        // Create 30 revisions (exceeds the limit of 25)
        for ($i = 0; $i < 30; $i++) {
            $this->revisionService->createRevision($post, "Revision $i");
        }

        // Should only have 25 revisions
        $this->assertEquals(25, $post->revisions()->count());
    }

    public function test_deletes_oldest_revisions_when_limit_exceeded(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user);

        // Create 26 revisions
        for ($i = 0; $i < 26; $i++) {
            $this->revisionService->createRevision($post, "Revision $i");
            sleep(0.01); // Small delay to ensure different timestamps
        }

        // Get all revisions
        $revisions = $post->revisions()->orderBy('created_at', 'asc')->get();

        // Should only have 25 revisions
        $this->assertEquals(25, $revisions->count());

        // The oldest revision (Revision 0) should be deleted
        $this->assertStringNotContainsString('Revision 0', $revisions->pluck('revision_note')->implode(','));

        // The newest revision (Revision 25) should exist
        $this->assertStringContainsString('Revision 25', $revisions->pluck('revision_note')->implode(','));
    }

    public function test_gets_revisions_for_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user);

        // Create 3 revisions
        for ($i = 0; $i < 3; $i++) {
            $this->revisionService->createRevision($post);
        }

        $revisions = $this->revisionService->getRevisions($post);

        $this->assertCount(3, $revisions);
        $this->assertTrue($revisions->first()->relationLoaded('user'));
    }

    public function test_restores_revision(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Original Title',
            'content' => 'Original content',
            'excerpt' => 'Original excerpt',
        ]);

        $this->actingAs($user);

        // Create a revision of the original state
        $originalRevision = $this->revisionService->createRevision($post);

        // Update the post
        $post->update([
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'excerpt' => 'Updated excerpt',
        ]);

        // Restore the original revision
        $restoredPost = $this->revisionService->restoreRevision($post, $originalRevision);

        $this->assertEquals('Original Title', $restoredPost->title);
        $this->assertEquals('Original content', $restoredPost->content);
        $this->assertEquals('Original excerpt', $restoredPost->excerpt);
    }

    public function test_restore_creates_new_revisions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user);

        // Create initial revision
        $revision = $this->revisionService->createRevision($post);

        // Update post
        $post->update(['title' => 'Updated']);

        $initialCount = $post->revisions()->count();

        // Restore revision (should create 2 new revisions: before restore and after restore)
        $this->revisionService->restoreRevision($post, $revision);

        $this->assertEquals($initialCount + 2, $post->revisions()->count());
    }

    public function test_compares_revisions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Old Title',
            'content' => 'Old content',
            'excerpt' => 'Old excerpt',
        ]);

        $this->actingAs($user);

        $oldRevision = $this->revisionService->createRevision($post);

        $post->update([
            'title' => 'New Title',
            'content' => 'New content',
            'excerpt' => 'New excerpt',
        ]);

        $newRevision = $this->revisionService->createRevision($post);

        $diff = $this->revisionService->compareRevisions($oldRevision, $newRevision);

        $this->assertTrue($diff['title']['changed']);
        $this->assertEquals('Old Title', $diff['title']['old']);
        $this->assertEquals('New Title', $diff['title']['new']);

        $this->assertTrue($diff['content']['changed']);
        $this->assertEquals('Old content', $diff['content']['old']);
        $this->assertEquals('New content', $diff['content']['new']);

        $this->assertTrue($diff['excerpt']['changed']);
        $this->assertEquals('Old excerpt', $diff['excerpt']['old']);
        $this->assertEquals('New excerpt', $diff['excerpt']['new']);
    }

    public function test_compare_detects_no_changes(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Same Title',
            'content' => 'Same content',
        ]);

        $this->actingAs($user);

        $revision1 = $this->revisionService->createRevision($post);
        $revision2 = $this->revisionService->createRevision($post);

        $diff = $this->revisionService->compareRevisions($revision1, $revision2);

        $this->assertFalse($diff['title']['changed']);
        $this->assertFalse($diff['content']['changed']);
    }

    public function test_generates_diff_for_content(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'content' => "Line 1\nLine 2\nLine 3",
        ]);

        $this->actingAs($user);

        $oldRevision = $this->revisionService->createRevision($post);

        $post->update([
            'content' => "Line 1\nLine 2 Modified\nLine 3",
        ]);

        $newRevision = $this->revisionService->createRevision($post);

        $diff = $this->revisionService->compareRevisions($oldRevision, $newRevision);

        $this->assertIsArray($diff['content']['diff']);
        $this->assertNotEmpty($diff['content']['diff']);
    }

    public function test_deletes_revision(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user);

        $revision = $this->revisionService->createRevision($post);
        $revisionId = $revision->id;

        $result = $this->revisionService->deleteRevision($revision);

        $this->assertTrue($result);
        $this->assertNull(PostRevision::find($revisionId));
    }

    public function test_gets_specific_revision(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user);

        $revision = $this->revisionService->createRevision($post);

        $retrieved = $this->revisionService->getRevision($revision->id);

        $this->assertInstanceOf(PostRevision::class, $retrieved);
        $this->assertEquals($revision->id, $retrieved->id);
        $this->assertTrue($retrieved->relationLoaded('user'));
        $this->assertTrue($retrieved->relationLoaded('post'));
    }

    public function test_stores_metadata_in_revision(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'slug' => 'test-slug',
            'status' => 'published',
            'featured_image' => 'image.jpg',
            'meta_title' => 'Meta Title',
            'meta_description' => 'Meta Description',
            'meta_keywords' => 'keyword1, keyword2',
        ]);

        $this->actingAs($user);

        $revision = $this->revisionService->createRevision($post);

        $this->assertIsArray($revision->meta_data);
        $this->assertEquals('test-slug', $revision->meta_data['slug']);
        $this->assertEquals('published', $revision->meta_data['status']);
        $this->assertEquals('image.jpg', $revision->meta_data['featured_image']);
        $this->assertEquals('Meta Title', $revision->meta_data['meta_title']);
        $this->assertEquals('Meta Description', $revision->meta_data['meta_description']);
        $this->assertEquals('keyword1, keyword2', $revision->meta_data['meta_keywords']);
    }
}
