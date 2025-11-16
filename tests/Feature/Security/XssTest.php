<?php

namespace Tests\Feature\Security;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XssTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_content_is_sanitized(): void
    {
        $post = Post::factory()->create();

        $payload = [
            'post_id' => $post->id,
            'author_name' => 'Eve',
            'author_email' => 'eve@example.com',
            'content' => '<img src=x onerror=alert(1)><script>alert(2)</script><b>ok</b>',
            '_token' => csrf_token(),
        ];

        // Use actingAs csrf cookie is not necessary; send token field
        $this->post('/comments', $payload)->assertStatus(302);

        $comment = \App\Models\Comment::first();
        $this->assertNotNull($comment);
        $this->assertStringNotContainsString('<script', $comment->content);
        $this->assertStringNotContainsString('onerror', strtolower($comment->content));
        $this->assertStringContainsString('<b>ok</b>', $comment->content);
    }
}


