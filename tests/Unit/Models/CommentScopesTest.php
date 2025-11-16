<?php

namespace Tests\Unit\Models;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_level_scope_returns_only_root_comments(): void
    {
        $post = Post::factory()->create();

        $root1 = Comment::factory()->create(['post_id' => $post->id, 'parent_id' => null]);
        $root2 = Comment::factory()->create(['post_id' => $post->id, 'parent_id' => null]);
        Comment::factory()->create(['post_id' => $post->id, 'parent_id' => $root1->id]);

        $top = Comment::query()->forPost($post->id)->topLevel()->get();

        $this->assertCount(2, $top);
        $this->assertTrue($top->contains($root1));
        $this->assertTrue($top->contains($root2));
    }
}
