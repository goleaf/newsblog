<?php

namespace Tests\Feature\Nova;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Nova\Comment as CommentResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class CommentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_resource_has_correct_model(): void
    {
        $this->assertEquals(\App\Models\Comment::class, CommentResource::$model);
    }

    public function test_comment_resource_has_correct_title(): void
    {
        $this->assertEquals('id', CommentResource::$title);
    }

    public function test_comment_resource_has_correct_search_fields(): void
    {
        $expected = ['id', 'content', 'author_name', 'author_email'];
        $this->assertEquals($expected, CommentResource::$search);
    }

    public function test_admin_can_view_any_comments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/comments', 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(CommentResource::authorizedToViewAny($request));
    }

    public function test_editor_can_view_any_comments(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $request = NovaRequest::create('/nova-api/comments', 'GET');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue(CommentResource::authorizedToViewAny($request));
    }

    public function test_author_cannot_view_any_comments(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $request = NovaRequest::create('/nova-api/comments', 'GET');
        $request->setUserResolver(fn () => $author);

        $this->assertFalse(CommentResource::authorizedToViewAny($request));
    }

    public function test_admin_can_view_comment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $resource = new CommentResource($comment);
        $request = NovaRequest::create('/nova-api/comments/'.$comment->id, 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToView($request));
    }

    public function test_editor_can_view_comment(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $resource = new CommentResource($comment);
        $request = NovaRequest::create('/nova-api/comments/'.$comment->id, 'GET');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue($resource->authorizedToView($request));
    }

    public function test_user_can_view_own_comment(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $resource = new CommentResource($comment);
        $request = NovaRequest::create('/nova-api/comments/'.$comment->id, 'GET');
        $request->setUserResolver(fn () => $user);

        $this->assertTrue($resource->authorizedToView($request));
    }

    public function test_user_cannot_view_other_users_comment(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $otherUser->id,
        ]);

        $resource = new CommentResource($comment);
        $request = NovaRequest::create('/nova-api/comments/'.$comment->id, 'GET');
        $request->setUserResolver(fn () => $user);

        $this->assertFalse($resource->authorizedToView($request));
    }

    public function test_admin_can_update_comment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $resource = new CommentResource($comment);
        $request = NovaRequest::create('/nova-api/comments/'.$comment->id, 'PUT');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToUpdate($request));
    }

    public function test_editor_can_update_comment(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $resource = new CommentResource($comment);
        $request = NovaRequest::create('/nova-api/comments/'.$comment->id, 'PUT');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue($resource->authorizedToUpdate($request));
    }

    public function test_admin_can_delete_comment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $resource = new CommentResource($comment);
        $request = NovaRequest::create('/nova-api/comments/'.$comment->id, 'DELETE');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToDelete($request));
    }

    public function test_editor_can_delete_comment(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $resource = new CommentResource($comment);
        $request = NovaRequest::create('/nova-api/comments/'.$comment->id, 'DELETE');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue($resource->authorizedToDelete($request));
    }

    public function test_comment_resource_has_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $resource = new CommentResource($comment);

        $request = NovaRequest::create('/nova-api/comments', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);

        $this->assertNotEmpty($fields);
        $this->assertGreaterThan(10, count($fields));
    }

    public function test_comment_index_query_eager_loads_relationships(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        Comment::factory()->create(['post_id' => $post->id]);

        $request = NovaRequest::create('/nova-api/comments', 'GET');
        $request->setUserResolver(fn () => $admin);

        $query = CommentResource::indexQuery($request, Comment::query());
        $comments = $query->get();

        $this->assertTrue($comments->first()->relationLoaded('post'));
        $this->assertTrue($comments->first()->relationLoaded('user'));
        $this->assertTrue($comments->first()->relationLoaded('parent'));
    }
}
