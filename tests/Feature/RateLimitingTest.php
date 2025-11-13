<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear rate limiters before each test
        RateLimiter::clear('login');
        RateLimiter::clear('comments');
        RateLimiter::clear('search');
    }

    public function test_login_rate_limiting_blocks_after_5_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Make 5 login attempts (should succeed or fail based on credentials)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('login'), [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

            // Should not be rate limited yet
            $this->assertNotEquals(429, $response->status(), "Request {$i} should not be rate limited");
        }

        // 6th attempt should be rate limited
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }

    public function test_login_rate_limiting_returns_json_response_with_message(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Exceed rate limit
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('login'), [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be rate limited with custom message
        $response = $this->postJson(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'message' => 'Too many login attempts. Please try again later.',
        ]);
    }

    public function test_comment_rate_limiting_blocks_after_3_attempts(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        // Submit 3 comments (should succeed)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->post(route('comments.store'), [
                'post_id' => $post->id,
                'author_name' => 'John Doe',
                'author_email' => 'john@example.com',
                'content' => "Comment number {$i}",
                'page_load_time' => time() - 10,
                'honeypot' => '',
            ]);

            $this->assertNotEquals(429, $response->status(), "Request {$i} should not be rate limited");
        }

        // 4th comment should be rate limited
        $response = $this->post(route('comments.store'), [
            'post_id' => $post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'Fourth comment',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }

    public function test_comment_rate_limiting_returns_json_response_with_message(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        // Exceed rate limit
        for ($i = 0; $i < 3; $i++) {
            $this->postJson(route('comments.store'), [
                'post_id' => $post->id,
                'author_name' => 'John Doe',
                'author_email' => 'john@example.com',
                'content' => "Comment {$i}",
                'page_load_time' => time() - 10,
                'honeypot' => '',
            ]);
        }

        // 4th comment should be rate limited with custom message
        $response = $this->postJson(route('comments.store'), [
            'post_id' => $post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'Fourth comment',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'message' => 'Too many comment submissions. Please slow down.',
        ]);
    }

    public function test_search_rate_limiting_works(): void
    {
        // Make 60 search requests (should succeed)
        for ($i = 0; $i < 60; $i++) {
            $response = $this->get(route('search', ['q' => 'test']));
            $this->assertNotEquals(429, $response->status(), "Request {$i} should not be rate limited");
        }

        // 61st request should be rate limited
        $response = $this->get(route('search', ['q' => 'test']));
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }

    public function test_rate_limit_response_includes_retry_after_header(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        // Exceed comment rate limit
        for ($i = 0; $i < 4; $i++) {
            $this->post(route('comments.store'), [
                'post_id' => $post->id,
                'author_name' => 'John Doe',
                'author_email' => 'john@example.com',
                'content' => "Comment {$i}",
                'page_load_time' => time() - 10,
                'honeypot' => '',
            ]);
        }

        $response = $this->post(route('comments.store'), [
            'post_id' => $post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'Another comment',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertStatus(429);
        $this->assertTrue($response->headers->has('Retry-After'));
        $this->assertIsNumeric($response->headers->get('Retry-After'));
    }

    public function test_rate_limit_json_response_includes_retry_after(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        // Exceed comment rate limit
        for ($i = 0; $i < 4; $i++) {
            $this->postJson(route('comments.store'), [
                'post_id' => $post->id,
                'author_name' => 'John Doe',
                'author_email' => 'john@example.com',
                'content' => "Comment {$i}",
                'page_load_time' => time() - 10,
                'honeypot' => '',
            ]);
        }

        $response = $this->postJson(route('comments.store'), [
            'post_id' => $post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'Another comment',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertStatus(429);
        $response->assertJsonStructure([
            'message',
        ]);
        $response->assertHeader('Retry-After');
    }

    public function test_rate_limit_displays_custom_error_page_for_web_requests(): void
    {
        // Exceed search rate limit (web request)
        for ($i = 0; $i < 60; $i++) {
            $this->get(route('search', ['q' => 'test']));
        }

        // 61st request should show 429 error page
        $response = $this->get(route('search', ['q' => 'test']));

        $response->assertStatus(429);
        $response->assertViewIs('errors.429');
        $response->assertViewHas('retry_after');
    }

    public function test_different_ips_have_separate_rate_limits(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        // First IP makes 3 requests
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1']);
        for ($i = 0; $i < 3; $i++) {
            $response = $this->post(route('comments.store'), [
                'post_id' => $post->id,
                'author_name' => 'John Doe',
                'author_email' => 'john@example.com',
                'content' => "Comment {$i}",
                'page_load_time' => time() - 10,
                'honeypot' => '',
            ]);
            $this->assertNotEquals(429, $response->status());
        }

        // Different IP should not be rate limited
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.2']);
        $response = $this->post(route('comments.store'), [
            'post_id' => $post->id,
            'author_name' => 'Jane Doe',
            'author_email' => 'jane@example.com',
            'content' => 'Comment from different IP',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertStatus(302); // Should succeed
    }

    public function test_login_rate_limit_is_per_email_and_ip(): void
    {
        User::factory()->create([
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
        ]);

        // Make 5 attempts for user1
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => 'user1@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // user1 should be rate limited
        $response = $this->post(route('login'), [
            'email' => 'user1@example.com',
            'password' => 'wrong-password',
        ]);
        $response->assertStatus(429);

        // user2 should not be rate limited (different email)
        $response = $this->post(route('login'), [
            'email' => 'user2@example.com',
            'password' => 'wrong-password',
        ]);
        $this->assertNotEquals(429, $response->status());
    }
}
