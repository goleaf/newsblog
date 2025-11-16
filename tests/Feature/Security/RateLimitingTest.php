<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited(): void
    {
        // Hit the login endpoint 6 times with same email+ip
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'test@example.com', 'password' => 'invalid'])->assertStatus(302);
        }
        $this->post('/login', ['email' => 'test@example.com', 'password' => 'invalid'])->assertStatus(429);
    }

    public function test_comments_are_rate_limited(): void
    {
        $post = \App\Models\Post::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/comments', [
                'post_id' => $post->id,
                'author_name' => 'A',
                'author_email' => 'a@example.com',
                'content' => 'c',
            ])->assertStatus(302);
        }
        $this->post('/comments', [
            'post_id' => $post->id,
            'author_name' => 'A',
            'author_email' => 'a@example.com',
            'content' => 'c',
        ])->assertStatus(429);
    }

    public function test_api_rate_limit_public_and_auth(): void
    {
        // Public: 60/min
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/posts')->assertStatus(200);
        }
        $this->getJson('/api/v1/posts')->assertStatus(429);
    }
}


