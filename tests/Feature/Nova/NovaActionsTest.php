<?php

namespace Tests\Feature\Nova;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Nova\Actions\ApproveComments;
use App\Nova\Actions\ExportPosts;
use App\Nova\Actions\FeaturePosts;
use App\Nova\Actions\PublishPosts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\ActionFields;
use Tests\TestCase;

class NovaActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $editor;

    protected User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->author = User::factory()->create(['role' => 'author']);
    }

    public function test_publish_posts_action_publishes_draft_posts(): void
    {
        $draftPost1 = Post::factory()->create(['status' => 'draft', 'published_at' => null]);
        $draftPost2 = Post::factory()->create(['status' => 'draft', 'published_at' => null]);

        $action = new PublishPosts;
        $fields = new ActionFields(collect([]), collect([]));
        $models = collect([$draftPost1, $draftPost2]);

        $result = $action->handle($fields, $models);

        $this->assertDatabaseHas('posts', [
            'id' => $draftPost1->id,
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('posts', [
            'id' => $draftPost2->id,
            'status' => 'published',
        ]);

        $draftPost1->refresh();
        $draftPost2->refresh();
        $this->assertNotNull($draftPost1->published_at);
        $this->assertNotNull($draftPost2->published_at);
    }

    public function test_publish_posts_action_skips_already_published_posts(): void
    {
        $publishedPost = Post::factory()->create(['status' => 'published']);
        $draftPost = Post::factory()->create(['status' => 'draft']);

        $action = new PublishPosts;
        $fields = new ActionFields(collect([]), collect([]));
        $models = collect([$publishedPost, $draftPost]);

        $result = $action->handle($fields, $models);

        $this->assertDatabaseHas('posts', [
            'id' => $draftPost->id,
            'status' => 'published',
        ]);
    }

    public function test_publish_posts_action_returns_danger_when_no_drafts(): void
    {
        $publishedPost = Post::factory()->create(['status' => 'published']);

        $action = new PublishPosts;
        $fields = new ActionFields(collect([]), collect([]));
        $models = collect([$publishedPost]);

        $result = $action->handle($fields, $models);

        $this->assertStringContainsString('No draft posts', $result);
    }

    public function test_publish_posts_action_only_visible_to_admin_and_editor(): void
    {
        $action = new PublishPosts;

        $adminRequest = \Illuminate\Http\Request::create('/', 'GET');
        $adminRequest->setUserResolver(fn () => $this->admin);
        $this->assertTrue($action->authorizedToSee($adminRequest));

        $editorRequest = \Illuminate\Http\Request::create('/', 'GET');
        $editorRequest->setUserResolver(fn () => $this->editor);
        $this->assertTrue($action->authorizedToSee($editorRequest));

        $authorRequest = \Illuminate\Http\Request::create('/', 'GET');
        $authorRequest->setUserResolver(fn () => $this->author);
        $this->assertFalse($action->authorizedToSee($authorRequest));
    }

    public function test_feature_posts_action_marks_posts_as_featured(): void
    {
        $post1 = Post::factory()->create(['is_featured' => false]);
        $post2 = Post::factory()->create(['is_featured' => false]);

        $action = new FeaturePosts;
        $fields = new ActionFields(collect(['action' => 'feature']), collect([]));
        $models = collect([$post1, $post2]);

        $result = $action->handle($fields, $models);

        $this->assertDatabaseHas('posts', [
            'id' => $post1->id,
            'is_featured' => true,
        ]);
        $this->assertDatabaseHas('posts', [
            'id' => $post2->id,
            'is_featured' => true,
        ]);
    }

    public function test_feature_posts_action_removes_featured_status(): void
    {
        $post1 = Post::factory()->create(['is_featured' => true]);
        $post2 = Post::factory()->create(['is_featured' => true]);

        $action = new FeaturePosts;
        $fields = new ActionFields(collect(['action' => 'unfeature']), collect([]));
        $models = collect([$post1, $post2]);

        $result = $action->handle($fields, $models);

        $this->assertDatabaseHas('posts', [
            'id' => $post1->id,
            'is_featured' => false,
        ]);
        $this->assertDatabaseHas('posts', [
            'id' => $post2->id,
            'is_featured' => false,
        ]);
    }

    public function test_feature_posts_action_skips_posts_already_in_desired_state(): void
    {
        $featuredPost = Post::factory()->create(['is_featured' => true]);
        $unfeaturedPost = Post::factory()->create(['is_featured' => false]);

        $action = new FeaturePosts;
        $fields = new ActionFields(collect(['action' => 'feature']), collect([]));
        $models = collect([$featuredPost, $unfeaturedPost]);

        $result = $action->handle($fields, $models);

        $this->assertStringContainsString('1 post(s) featured', $result);
    }

    public function test_feature_posts_action_only_visible_to_admin_and_editor(): void
    {
        $action = new FeaturePosts;

        $adminRequest = \Illuminate\Http\Request::create('/', 'GET');
        $adminRequest->setUserResolver(fn () => $this->admin);
        $this->assertTrue($action->authorizedToSee($adminRequest));

        $editorRequest = \Illuminate\Http\Request::create('/', 'GET');
        $editorRequest->setUserResolver(fn () => $this->editor);
        $this->assertTrue($action->authorizedToSee($editorRequest));

        $authorRequest = \Illuminate\Http\Request::create('/', 'GET');
        $authorRequest->setUserResolver(fn () => $this->author);
        $this->assertFalse($action->authorizedToSee($authorRequest));
    }

    public function test_approve_comments_action_approves_pending_comments(): void
    {
        $post = Post::factory()->create();
        $comment1 = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);
        $comment2 = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);

        $action = new ApproveComments;
        $fields = new ActionFields(collect([]), collect([]));
        $models = collect([$comment1, $comment2]);

        $result = $action->handle($fields, $models);

        $this->assertDatabaseHas('comments', [
            'id' => $comment1->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('comments', [
            'id' => $comment2->id,
            'status' => 'approved',
        ]);
    }

    public function test_approve_comments_action_skips_already_approved_comments(): void
    {
        $post = Post::factory()->create();
        $approvedComment = Comment::factory()->create(['post_id' => $post->id, 'status' => 'approved']);
        $pendingComment = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);

        $action = new ApproveComments;
        $fields = new ActionFields(collect([]), collect([]));
        $models = collect([$approvedComment, $pendingComment]);

        $result = $action->handle($fields, $models);

        $this->assertStringContainsString('1 comment(s) approved', $result);
    }

    public function test_approve_comments_action_only_visible_to_admin_and_editor(): void
    {
        $action = new ApproveComments;

        $adminRequest = \Illuminate\Http\Request::create('/', 'GET');
        $adminRequest->setUserResolver(fn () => $this->admin);
        $this->assertTrue($action->authorizedToSee($adminRequest));

        $editorRequest = \Illuminate\Http\Request::create('/', 'GET');
        $editorRequest->setUserResolver(fn () => $this->editor);
        $this->assertTrue($action->authorizedToSee($editorRequest));

        $authorRequest = \Illuminate\Http\Request::create('/', 'GET');
        $authorRequest->setUserResolver(fn () => $this->author);
        $this->assertFalse($action->authorizedToSee($authorRequest));
    }

    public function test_export_posts_action_creates_csv_file(): void
    {
        Storage::fake('public');

        $post1 = Post::factory()->create(['title' => 'First Post']);
        $post2 = Post::factory()->create(['title' => 'Second Post']);

        $action = new ExportPosts;
        $fields = new ActionFields(collect([]), collect([]));
        $models = collect([$post1, $post2]);

        $result = $action->handle($fields, $models);

        // Check that a CSV file was created
        $files = Storage::disk('public')->files();
        $this->assertNotEmpty($files);

        $csvFile = $files[0];
        $this->assertStringContainsString('posts_export_', $csvFile);
        $this->assertStringEndsWith('.csv', $csvFile);

        // Verify CSV content
        $content = Storage::disk('public')->get($csvFile);
        $this->assertStringContainsString('First Post', $content);
        $this->assertStringContainsString('Second Post', $content);
        $this->assertStringContainsString('ID', $content);
        $this->assertStringContainsString('Title', $content);
    }

    public function test_export_posts_action_includes_all_required_fields(): void
    {
        Storage::fake('public');

        $post = Post::factory()->create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'status' => 'published',
            'is_featured' => true,
            'view_count' => 100,
        ]);

        $action = new ExportPosts;
        $fields = new ActionFields(collect([]), collect([]));
        $models = collect([$post]);

        $result = $action->handle($fields, $models);

        $files = Storage::disk('public')->files();
        $content = Storage::disk('public')->get($files[0]);

        $this->assertStringContainsString('Test Post', $content);
        $this->assertStringContainsString('test-post', $content);
        $this->assertStringContainsString('Published', $content);
        $this->assertStringContainsString('Yes', $content); // is_featured
        $this->assertStringContainsString('100', $content); // view_count
    }

    public function test_export_posts_action_only_visible_to_admin_and_editor(): void
    {
        $action = new ExportPosts;

        $adminRequest = \Illuminate\Http\Request::create('/', 'GET');
        $adminRequest->setUserResolver(fn () => $this->admin);
        $this->assertTrue($action->authorizedToSee($adminRequest));

        $editorRequest = \Illuminate\Http\Request::create('/', 'GET');
        $editorRequest->setUserResolver(fn () => $this->editor);
        $this->assertTrue($action->authorizedToSee($editorRequest));

        $authorRequest = \Illuminate\Http\Request::create('/', 'GET');
        $authorRequest->setUserResolver(fn () => $this->author);
        $this->assertFalse($action->authorizedToSee($authorRequest));
    }
}
