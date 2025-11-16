<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\UserResource;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ResourceSerializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_resource_serializes_core_fields(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $data = (new PostResource($post->fresh()))->toArray(new Request);

        $this->assertSame($post->id, $data['id']);
        $this->assertSame($post->slug, $data['slug']);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('url', $data);
    }

    public function test_user_resource_hides_email_for_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $request = Request::create('/');
        $request->setUserResolver(fn () => $other);

        $data = (new UserResource($user))->toArray($request);

        $this->assertArrayNotHasKey('email', $data);
    }

    public function test_user_resource_shows_email_for_self(): void
    {
        $user = User::factory()->create();

        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        $data = (new UserResource($user))->toArray($request);

        $this->assertSame($user->email, $data['email']);
    }

    public function test_comment_resource_basic_shape(): void
    {
        $comment = Comment::factory()->create();
        $data = (new CommentResource($comment))->toArray(new Request);

        $this->assertSame($comment->id, $data['id']);
        $this->assertSame($comment->post_id, $data['post_id']);
        $this->assertArrayHasKey('content', $data);
    }

    public function test_category_and_tag_resources_basic_shape(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $categoryData = (new CategoryResource($category))->toArray(new Request);
        $tagData = (new TagResource($tag))->toArray(new Request);

        $this->assertSame($category->slug, $categoryData['slug']);
        $this->assertSame($tag->slug, $tagData['slug']);
    }
}
