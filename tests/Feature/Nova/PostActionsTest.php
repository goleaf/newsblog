<?php

namespace Tests\Feature\Nova;

use App\Models\Post;
use App\Models\User;
use App\Nova\Actions\ExportPosts;
use App\Nova\Actions\FeaturePosts;
use App\Nova\Actions\PublishPosts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class PostActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_posts_action_publishes_draft_posts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $draftPost1 = Post::factory()->create(['status' => 'draft', 'published_at' => null]);
        $draftPost2 = Post::factory()->create(['status' => 'draft', 'published_at' => null]);

        $action = new PublishPosts;
        $models = new Collection([$draftPost1, $draftPost2]);
        $fields = new ActionFields(collect([]), collect([]));

        $action->handle($fields, $models);

        $draftPost1->refresh();
        $draftPost2->refresh();

        $this->assertEquals('published', $draftPost1->status);
        $this->assertEquals('published', $draftPost2->status);
        $this->assertNotNull($draftPost1->published_at);
        $this->assertNotNull($draftPost2->published_at);
    }

    public function test_publish_posts_action_skips_already_published_posts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $publishedPost = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $originalPublishedAt = $publishedPost->published_at;

        $action = new PublishPosts;
        $models = new Collection([$publishedPost]);
        $fields = new ActionFields(collect([]), collect([]));

        $action->handle($fields, $models);

        $publishedPost->refresh();

        $this->assertEquals('published', $publishedPost->status);
        $this->assertEquals($originalPublishedAt->timestamp, $publishedPost->published_at->timestamp);
    }

    public function test_publish_posts_action_only_visible_to_admin_and_editor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $editor = User::factory()->create(['role' => 'editor']);
        $author = User::factory()->create(['role' => 'author']);
        $user = User::factory()->create(['role' => 'user']);

        $action = new PublishPosts;

        $adminRequest = NovaRequest::create('/nova-api/posts/action', 'POST');
        $adminRequest->setUserResolver(fn () => $admin);
        $this->assertTrue($action->authorizedToSee($adminRequest));

        $editorRequest = NovaRequest::create('/nova-api/posts/action', 'POST');
        $editorRequest->setUserResolver(fn () => $editor);
        $this->assertTrue($action->authorizedToSee($editorRequest));

        $authorRequest = NovaRequest::create('/nova-api/posts/action', 'POST');
        $authorRequest->setUserResolver(fn () => $author);
        $this->assertFalse($action->authorizedToSee($authorRequest));

        $userRequest = NovaRequest::create('/nova-api/posts/action', 'POST');
        $userRequest->setUserResolver(fn () => $user);
        $this->assertFalse($action->authorizedToSee($userRequest));
    }

    public function test_feature_posts_action_marks_posts_as_featured(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post1 = Post::factory()->create(['is_featured' => false]);
        $post2 = Post::factory()->create(['is_featured' => false]);

        $action = new FeaturePosts;
        $models = new Collection([$post1, $post2]);
        $fields = new ActionFields(collect(['action' => 'feature']), collect([]));

        $action->handle($fields, $models);

        $post1->refresh();
        $post2->refresh();

        $this->assertTrue($post1->is_featured);
        $this->assertTrue($post2->is_featured);
    }

    public function test_feature_posts_action_removes_featured_flag(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post1 = Post::factory()->create(['is_featured' => true]);
        $post2 = Post::factory()->create(['is_featured' => true]);

        $action = new FeaturePosts;
        $models = new Collection([$post1, $post2]);
        $fields = new ActionFields(collect(['action' => 'unfeature']), collect([]));

        $action->handle($fields, $models);

        $post1->refresh();
        $post2->refresh();

        $this->assertFalse($post1->is_featured);
        $this->assertFalse($post2->is_featured);
    }

    public function test_feature_posts_action_only_visible_to_admin_and_editor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $editor = User::factory()->create(['role' => 'editor']);
        $author = User::factory()->create(['role' => 'author']);

        $action = new FeaturePosts;

        $adminRequest = NovaRequest::create('/nova-api/posts/action', 'POST');
        $adminRequest->setUserResolver(fn () => $admin);
        $this->assertTrue($action->authorizedToSee($adminRequest));

        $editorRequest = NovaRequest::create('/nova-api/posts/action', 'POST');
        $editorRequest->setUserResolver(fn () => $editor);
        $this->assertTrue($action->authorizedToSee($editorRequest));

        $authorRequest = NovaRequest::create('/nova-api/posts/action', 'POST');
        $authorRequest->setUserResolver(fn () => $author);
        $this->assertFalse($action->authorizedToSee($authorRequest));
    }

    public function test_export_posts_action_creates_csv_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = \App\Models\Category::factory()->create(['name' => 'Tech']);
        $post1 = Post::factory()->create([
            'title' => 'Test Post 1',
            'slug' => 'test-post-1',
            'status' => 'published',
            'is_featured' => true,
            'view_count' => 100,
            'category_id' => $category->id,
            'user_id' => $admin->id,
        ]);
        $post2 = Post::factory()->create([
            'title' => 'Test Post 2',
            'slug' => 'test-post-2',
            'status' => 'draft',
            'is_featured' => false,
            'view_count' => 50,
            'category_id' => $category->id,
            'user_id' => $admin->id,
        ]);

        $action = new ExportPosts;
        $models = new Collection([$post1, $post2]);
        $fields = new ActionFields(collect([]), collect([]));

        $result = $action->handle($fields, $models);

        $this->assertNotNull($result);
    }

    public function test_export_posts_action_only_visible_to_admin_and_editor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $editor = User::factory()->create(['role' => 'editor']);
        $author = User::factory()->create(['role' => 'author']);

        $action = new ExportPosts;

        $adminRequest = NovaRequest::create('/nova-api/posts/action', 'POST');
        $adminRequest->setUserResolver(fn () => $admin);
        $this->assertTrue($action->authorizedToSee($adminRequest));

        $editorRequest = NovaRequest::create('/nova-api/posts/action', 'POST');
        $editorRequest->setUserResolver(fn () => $editor);
        $this->assertTrue($action->authorizedToSee($editorRequest));

        $authorRequest = NovaRequest::create('/nova-api/posts/action', 'POST');
        $authorRequest->setUserResolver(fn () => $author);
        $this->assertFalse($action->authorizedToSee($authorRequest));
    }
}
