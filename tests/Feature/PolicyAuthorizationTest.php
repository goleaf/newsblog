<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Newsletter;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $editor;

    protected User $author;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->author = User::factory()->create(['role' => 'author']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    // Post Policy Tests
    public function test_post_policy_view_any(): void
    {
        $this->assertTrue($this->admin->can('viewAny', Post::class));
        $this->assertTrue($this->editor->can('viewAny', Post::class));
        $this->assertTrue($this->author->can('viewAny', Post::class));
        $this->assertFalse($this->user->can('viewAny', Post::class));
    }

    public function test_post_policy_create(): void
    {
        $this->assertTrue($this->admin->can('create', Post::class));
        $this->assertTrue($this->editor->can('create', Post::class));
        $this->assertTrue($this->author->can('create', Post::class));
        $this->assertFalse($this->user->can('create', Post::class));
    }

    public function test_post_policy_update(): void
    {
        $post = Post::factory()->create(['user_id' => $this->author->id]);
        $otherPost = Post::factory()->create(['user_id' => $this->admin->id]);

        // Admin and editor can update any post
        $this->assertTrue($this->admin->can('update', $post));
        $this->assertTrue($this->editor->can('update', $post));

        // Author can update their own post
        $this->assertTrue($this->author->can('update', $post));
        $this->assertFalse($this->author->can('update', $otherPost));

        // Regular user cannot update posts
        $this->assertFalse($this->user->can('update', $post));
    }

    public function test_post_policy_delete(): void
    {
        $post = Post::factory()->create(['user_id' => $this->author->id]);
        $otherPost = Post::factory()->create(['user_id' => $this->admin->id]);

        // Admin and editor can delete any post
        $this->assertTrue($this->admin->can('delete', $post));
        $this->assertTrue($this->editor->can('delete', $post));

        // Author can delete their own post
        $this->assertTrue($this->author->can('delete', $post));
        $this->assertFalse($this->author->can('delete', $otherPost));

        // Regular user cannot delete posts
        $this->assertFalse($this->user->can('delete', $post));
    }

    // User Policy Tests
    public function test_user_policy_view_any(): void
    {
        $this->assertTrue($this->admin->can('viewAny', User::class));
        $this->assertTrue($this->editor->can('viewAny', User::class));
        $this->assertFalse($this->author->can('viewAny', User::class));
        $this->assertFalse($this->user->can('viewAny', User::class));
    }

    public function test_user_policy_create(): void
    {
        $this->assertTrue($this->admin->can('create', User::class));
        $this->assertFalse($this->editor->can('create', User::class));
        $this->assertFalse($this->author->can('create', User::class));
        $this->assertFalse($this->user->can('create', User::class));
    }

    public function test_user_policy_update(): void
    {
        $otherUser = User::factory()->create();

        // Admin can update any user
        $this->assertTrue($this->admin->can('update', $otherUser));

        // Users can update their own profile
        $this->assertTrue($this->editor->can('update', $this->editor));
        $this->assertFalse($this->editor->can('update', $otherUser));
    }

    public function test_user_policy_delete(): void
    {
        $otherUser = User::factory()->create();

        $this->assertTrue($this->admin->can('delete', $otherUser));
        $this->assertFalse($this->editor->can('delete', $otherUser));
        $this->assertFalse($this->author->can('delete', $otherUser));
        $this->assertFalse($this->user->can('delete', $otherUser));
    }

    // Category Policy Tests
    public function test_category_policy_view_any(): void
    {
        $this->assertTrue($this->admin->can('viewAny', Category::class));
        $this->assertTrue($this->editor->can('viewAny', Category::class));
        $this->assertFalse($this->author->can('viewAny', Category::class));
        $this->assertFalse($this->user->can('viewAny', Category::class));
    }

    public function test_category_policy_create(): void
    {
        $this->assertTrue($this->admin->can('create', Category::class));
        $this->assertTrue($this->editor->can('create', Category::class));
        $this->assertFalse($this->author->can('create', Category::class));
        $this->assertFalse($this->user->can('create', Category::class));
    }

    public function test_category_policy_update(): void
    {
        $category = Category::factory()->create();

        $this->assertTrue($this->admin->can('update', $category));
        $this->assertTrue($this->editor->can('update', $category));
        $this->assertFalse($this->author->can('update', $category));
        $this->assertFalse($this->user->can('update', $category));
    }

    public function test_category_policy_delete(): void
    {
        $category = Category::factory()->create();

        $this->assertTrue($this->admin->can('delete', $category));
        $this->assertTrue($this->editor->can('delete', $category));
        $this->assertFalse($this->author->can('delete', $category));
        $this->assertFalse($this->user->can('delete', $category));
    }

    // Tag Policy Tests
    public function test_tag_policy_view_any(): void
    {
        $this->assertTrue($this->admin->can('viewAny', Tag::class));
        $this->assertTrue($this->editor->can('viewAny', Tag::class));
        $this->assertFalse($this->author->can('viewAny', Tag::class));
        $this->assertFalse($this->user->can('viewAny', Tag::class));
    }

    public function test_tag_policy_create(): void
    {
        $this->assertTrue($this->admin->can('create', Tag::class));
        $this->assertTrue($this->editor->can('create', Tag::class));
        $this->assertFalse($this->author->can('create', Tag::class));
        $this->assertFalse($this->user->can('create', Tag::class));
    }

    public function test_tag_policy_update(): void
    {
        $tag = Tag::factory()->create();

        $this->assertTrue($this->admin->can('update', $tag));
        $this->assertTrue($this->editor->can('update', $tag));
        $this->assertFalse($this->author->can('update', $tag));
        $this->assertFalse($this->user->can('update', $tag));
    }

    public function test_tag_policy_delete(): void
    {
        $tag = Tag::factory()->create();

        $this->assertTrue($this->admin->can('delete', $tag));
        $this->assertTrue($this->editor->can('delete', $tag));
        $this->assertFalse($this->author->can('delete', $tag));
        $this->assertFalse($this->user->can('delete', $tag));
    }

    // Comment Policy Tests
    public function test_comment_policy_view_any(): void
    {
        $this->assertTrue($this->admin->can('viewAny', Comment::class));
        $this->assertTrue($this->editor->can('viewAny', Comment::class));
        $this->assertFalse($this->author->can('viewAny', Comment::class));
        $this->assertFalse($this->user->can('viewAny', Comment::class));
    }

    public function test_comment_policy_create(): void
    {
        $this->assertTrue($this->admin->can('create', Comment::class));
        $this->assertTrue($this->editor->can('create', Comment::class));
        $this->assertTrue($this->author->can('create', Comment::class));
        $this->assertTrue($this->user->can('create', Comment::class));
    }

    public function test_comment_policy_update(): void
    {
        $comment = Comment::factory()->create(['user_id' => $this->author->id]);
        $otherComment = Comment::factory()->create(['user_id' => $this->admin->id]);

        // Admin and editor can update any comment
        $this->assertTrue($this->admin->can('update', $comment));
        $this->assertTrue($this->editor->can('update', $comment));

        // Users can update their own comments
        $this->assertTrue($this->author->can('update', $comment));
        $this->assertFalse($this->author->can('update', $otherComment));
    }

    public function test_comment_policy_delete(): void
    {
        $comment = Comment::factory()->create(['user_id' => $this->author->id]);
        $otherComment = Comment::factory()->create(['user_id' => $this->admin->id]);

        // Admin and editor can delete any comment
        $this->assertTrue($this->admin->can('delete', $comment));
        $this->assertTrue($this->editor->can('delete', $comment));

        // Users can delete their own comments
        $this->assertTrue($this->author->can('delete', $comment));
        $this->assertFalse($this->author->can('delete', $otherComment));
    }

    // Media Policy Tests
    public function test_media_policy_view_any(): void
    {
        $this->assertTrue($this->admin->can('viewAny', Media::class));
        $this->assertTrue($this->editor->can('viewAny', Media::class));
        $this->assertTrue($this->author->can('viewAny', Media::class));
        $this->assertTrue($this->user->can('viewAny', Media::class));
    }

    public function test_media_policy_create(): void
    {
        $this->assertTrue($this->admin->can('create', Media::class));
        $this->assertTrue($this->editor->can('create', Media::class));
        $this->assertTrue($this->author->can('create', Media::class));
        $this->assertTrue($this->user->can('create', Media::class));
    }

    public function test_media_policy_update(): void
    {
        $media = Media::factory()->create(['user_id' => $this->author->id]);
        $otherMedia = Media::factory()->create(['user_id' => $this->admin->id]);

        // Admin and editor can update any media
        $this->assertTrue($this->admin->can('update', $media));
        $this->assertTrue($this->editor->can('update', $media));

        // Users can update their own media
        $this->assertTrue($this->author->can('update', $media));
        $this->assertFalse($this->author->can('update', $otherMedia));
    }

    public function test_media_policy_delete(): void
    {
        $media = Media::factory()->create(['user_id' => $this->author->id]);
        $otherMedia = Media::factory()->create(['user_id' => $this->admin->id]);

        // Admin and editor can delete any media
        $this->assertTrue($this->admin->can('delete', $media));
        $this->assertTrue($this->editor->can('delete', $media));

        // Users can delete their own media
        $this->assertTrue($this->author->can('delete', $media));
        $this->assertFalse($this->author->can('delete', $otherMedia));
    }

    // Page Policy Tests
    public function test_page_policy_view_any(): void
    {
        $this->assertTrue($this->admin->can('viewAny', Page::class));
        $this->assertTrue($this->editor->can('viewAny', Page::class));
        $this->assertFalse($this->author->can('viewAny', Page::class));
        $this->assertFalse($this->user->can('viewAny', Page::class));
    }

    public function test_page_policy_create(): void
    {
        $this->assertTrue($this->admin->can('create', Page::class));
        $this->assertTrue($this->editor->can('create', Page::class));
        $this->assertFalse($this->author->can('create', Page::class));
        $this->assertFalse($this->user->can('create', Page::class));
    }

    public function test_page_policy_update(): void
    {
        $page = Page::factory()->create();

        $this->assertTrue($this->admin->can('update', $page));
        $this->assertTrue($this->editor->can('update', $page));
        $this->assertFalse($this->author->can('update', $page));
        $this->assertFalse($this->user->can('update', $page));
    }

    public function test_page_policy_delete(): void
    {
        $page = Page::factory()->create();

        $this->assertTrue($this->admin->can('delete', $page));
        $this->assertTrue($this->editor->can('delete', $page));
        $this->assertFalse($this->author->can('delete', $page));
        $this->assertFalse($this->user->can('delete', $page));
    }

    // Newsletter Policy Tests
    public function test_newsletter_policy_view_any(): void
    {
        $this->assertTrue($this->admin->can('viewAny', Newsletter::class));
        $this->assertFalse($this->editor->can('viewAny', Newsletter::class));
        $this->assertFalse($this->author->can('viewAny', Newsletter::class));
        $this->assertFalse($this->user->can('viewAny', Newsletter::class));
    }

    public function test_newsletter_policy_create(): void
    {
        $this->assertTrue($this->admin->can('create', Newsletter::class));
        $this->assertFalse($this->editor->can('create', Newsletter::class));
        $this->assertFalse($this->author->can('create', Newsletter::class));
        $this->assertFalse($this->user->can('create', Newsletter::class));
    }

    public function test_newsletter_policy_update(): void
    {
        $newsletter = Newsletter::factory()->create();

        $this->assertTrue($this->admin->can('update', $newsletter));
        $this->assertFalse($this->editor->can('update', $newsletter));
        $this->assertFalse($this->author->can('update', $newsletter));
        $this->assertFalse($this->user->can('update', $newsletter));
    }

    public function test_newsletter_policy_delete(): void
    {
        $newsletter = Newsletter::factory()->create();

        $this->assertTrue($this->admin->can('delete', $newsletter));
        $this->assertFalse($this->editor->can('delete', $newsletter));
        $this->assertFalse($this->author->can('delete', $newsletter));
        $this->assertFalse($this->user->can('delete', $newsletter));
    }

    // Setting Policy Tests
    public function test_setting_policy_view_any(): void
    {
        $this->assertTrue($this->admin->can('viewAny', Setting::class));
        $this->assertFalse($this->editor->can('viewAny', Setting::class));
        $this->assertFalse($this->author->can('viewAny', Setting::class));
        $this->assertFalse($this->user->can('viewAny', Setting::class));
    }

    public function test_setting_policy_create(): void
    {
        $this->assertTrue($this->admin->can('create', Setting::class));
        $this->assertFalse($this->editor->can('create', Setting::class));
        $this->assertFalse($this->author->can('create', Setting::class));
        $this->assertFalse($this->user->can('create', Setting::class));
    }

    public function test_setting_policy_update(): void
    {
        $setting = Setting::factory()->create();

        $this->assertTrue($this->admin->can('update', $setting));
        $this->assertFalse($this->editor->can('update', $setting));
        $this->assertFalse($this->author->can('update', $setting));
        $this->assertFalse($this->user->can('update', $setting));
    }

    public function test_setting_policy_delete(): void
    {
        $setting = Setting::factory()->create();

        $this->assertTrue($this->admin->can('delete', $setting));
        $this->assertFalse($this->editor->can('delete', $setting));
        $this->assertFalse($this->author->can('delete', $setting));
        $this->assertFalse($this->user->can('delete', $setting));
    }

    // ActivityLog Policy Tests
    public function test_activity_log_policy_view_any(): void
    {
        $this->assertTrue($this->admin->can('viewAny', ActivityLog::class));
        $this->assertTrue($this->editor->can('viewAny', ActivityLog::class));
        $this->assertFalse($this->author->can('viewAny', ActivityLog::class));
        $this->assertFalse($this->user->can('viewAny', ActivityLog::class));
    }

    public function test_activity_log_policy_view(): void
    {
        $log = ActivityLog::factory()->create();

        $this->assertTrue($this->admin->can('view', $log));
        $this->assertTrue($this->editor->can('view', $log));
        $this->assertFalse($this->author->can('view', $log));
        $this->assertFalse($this->user->can('view', $log));
    }

    public function test_activity_log_policy_create(): void
    {
        // Activity logs are system-generated only
        $this->assertFalse($this->admin->can('create', ActivityLog::class));
        $this->assertFalse($this->editor->can('create', ActivityLog::class));
        $this->assertFalse($this->author->can('create', ActivityLog::class));
        $this->assertFalse($this->user->can('create', ActivityLog::class));
    }

    public function test_activity_log_policy_update(): void
    {
        $log = ActivityLog::factory()->create();

        // Activity logs are read-only
        $this->assertFalse($this->admin->can('update', $log));
        $this->assertFalse($this->editor->can('update', $log));
        $this->assertFalse($this->author->can('update', $log));
        $this->assertFalse($this->user->can('update', $log));
    }

    public function test_activity_log_policy_delete(): void
    {
        $log = ActivityLog::factory()->create();

        // Activity logs are read-only
        $this->assertFalse($this->admin->can('delete', $log));
        $this->assertFalse($this->editor->can('delete', $log));
        $this->assertFalse($this->author->can('delete', $log));
        $this->assertFalse($this->user->can('delete', $log));
    }
}
