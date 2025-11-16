<?php

namespace Tests\Feature\Security;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_form_requires_csrf_token(): void
    {
        $post = Post::factory()->create();

        $this->withHeaders([])->post('/comments', [
            'post_id' => $post->id,
            'author_name' => 'Alice',
            'author_email' => 'alice@example.com',
            'content' => 'Hello',
        ])->assertStatus(419);
    }
}


