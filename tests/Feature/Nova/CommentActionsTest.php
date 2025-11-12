<?php

namespace Tests\Feature\Nova;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Nova\Actions\ApproveComments;
use App\Nova\Actions\RejectComments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class CommentActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_comments_action_approves_pending_comments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        $comment1 = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);
        $comment2 = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);

        $action = new ApproveComments;
        $models = new Collection([$comment1, $comment2]);
        $fields = new ActionFields(collect([]), collect([]));

        $action->handle($fields, $models);

        $comment1->refresh();
        $comment2->refresh();

        $this->assertEquals('approved', $comment1->status);
        $this->assertEquals('approved', $comment2->status);
    }

    public function test_approve_comments_action_skips_already_approved_comments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id, 'status' => 'approved']);

        $action = new ApproveComments;
        $models = new Collection([$comment]);
        $fields = new ActionFields(collect([]), collect([]));

        $action->handle($fields, $models);

        $comment->refresh();

        $this->assertEquals('approved', $comment->status);
    }

    public function test_approve_comments_action_only_visible_to_admin_and_editor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $editor = User::factory()->create(['role' => 'editor']);
        $author = User::factory()->create(['role' => 'author']);
        $user = User::factory()->create(['role' => 'user']);

        $action = new ApproveComments;

        $adminRequest = NovaRequest::create('/nova-api/comments/action', 'POST');
        $adminRequest->setUserResolver(fn () => $admin);
        $this->assertTrue($action->authorizedToSee($adminRequest));

        $editorRequest = NovaRequest::create('/nova-api/comments/action', 'POST');
        $editorRequest->setUserResolver(fn () => $editor);
        $this->assertTrue($action->authorizedToSee($editorRequest));

        $authorRequest = NovaRequest::create('/nova-api/comments/action', 'POST');
        $authorRequest->setUserResolver(fn () => $author);
        $this->assertFalse($action->authorizedToSee($authorRequest));

        $userRequest = NovaRequest::create('/nova-api/comments/action', 'POST');
        $userRequest->setUserResolver(fn () => $user);
        $this->assertFalse($action->authorizedToSee($userRequest));
    }

    public function test_reject_comments_action_marks_comments_as_spam(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        $comment1 = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);
        $comment2 = Comment::factory()->create(['post_id' => $post->id, 'status' => 'approved']);

        $action = new RejectComments;
        $models = new Collection([$comment1, $comment2]);
        $fields = new ActionFields(collect([]), collect([]));

        $action->handle($fields, $models);

        $comment1->refresh();
        $comment2->refresh();

        $this->assertEquals('spam', $comment1->status);
        $this->assertEquals('spam', $comment2->status);
    }

    public function test_reject_comments_action_skips_already_spam_comments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id, 'status' => 'spam']);

        $action = new RejectComments;
        $models = new Collection([$comment]);
        $fields = new ActionFields(collect([]), collect([]));

        $action->handle($fields, $models);

        $comment->refresh();

        $this->assertEquals('spam', $comment->status);
    }

    public function test_reject_comments_action_only_visible_to_admin_and_editor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $editor = User::factory()->create(['role' => 'editor']);
        $author = User::factory()->create(['role' => 'author']);

        $action = new RejectComments;

        $adminRequest = NovaRequest::create('/nova-api/comments/action', 'POST');
        $adminRequest->setUserResolver(fn () => $admin);
        $this->assertTrue($action->authorizedToSee($adminRequest));

        $editorRequest = NovaRequest::create('/nova-api/comments/action', 'POST');
        $editorRequest->setUserResolver(fn () => $editor);
        $this->assertTrue($action->authorizedToSee($editorRequest));

        $authorRequest = NovaRequest::create('/nova-api/comments/action', 'POST');
        $authorRequest->setUserResolver(fn () => $author);
        $this->assertFalse($action->authorizedToSee($authorRequest));
    }
}
