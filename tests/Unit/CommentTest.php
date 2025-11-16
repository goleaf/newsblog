<?php

namespace Tests\Unit;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_uses_enum_cast_for_status(): void
    {
        $post = Post::factory()->create();

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => CommentStatus::Approved->value,
        ]);

        $this->assertInstanceOf(CommentStatus::class, $comment->status);
        $this->assertTrue($comment->isApproved());
    }

    public function test_comment_relationships_are_configured(): void
    {
        $post = Post::factory()->create();
        $parent = Comment::factory()->create([
            'post_id' => $post->id,
            'parent_id' => null,
        ]);

        $reply = Comment::factory()->create([
            'post_id' => $post->id,
            'parent_id' => $parent->id,
            'status' => CommentStatus::Approved->value,
        ]);

        $this->assertTrue($parent->replies->contains($reply));
        $this->assertTrue($reply->parent->is($parent));
        $this->assertTrue($post->comments->contains($parent));
    }

    public function test_scopes_filter_comments_correctly(): void
    {
        $post = Post::factory()->create();

        $pending = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => CommentStatus::Pending->value,
        ]);

        $approved = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => CommentStatus::Approved->value,
        ]);

        $this->assertTrue(Comment::approved()->get()->contains($approved));
        $this->assertFalse(Comment::approved()->get()->contains($pending));

        $this->assertTrue(Comment::pending()->get()->contains($pending));
        $this->assertFalse(Comment::pending()->get()->contains($approved));

        $this->assertTrue(Comment::forPost($post->id)->get()->contains($approved));
        $this->assertTrue(Comment::forPost($post->id)->get()->contains($pending));
    }
}
