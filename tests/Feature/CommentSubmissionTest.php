<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_legitimate_comment_is_marked_as_pending(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->post(route('comments.store'), [
            'post_id' => $post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'This is a great article! Thanks for sharing.',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_comment_with_excessive_links_is_marked_as_spam(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->post(route('comments.store'), [
            'post_id' => $post->id,
            'author_name' => 'Spammer',
            'author_email' => 'spam@example.com',
            'content' => 'Check http://spam1.com and http://spam2.com and http://spam3.com and http://spam4.com',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'status' => 'spam',
        ]);
    }

    public function test_comment_with_blacklisted_keywords_is_marked_as_spam(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->post(route('comments.store'), [
            'post_id' => $post->id,
            'author_name' => 'Spammer',
            'author_email' => 'spam@example.com',
            'content' => 'Buy viagra now!',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'status' => 'spam',
        ]);
    }

    public function test_comment_submitted_too_quickly_is_marked_as_spam(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->post(route('comments.store'), [
            'post_id' => $post->id,
            'author_name' => 'Bot',
            'author_email' => 'bot@example.com',
            'content' => 'Quick comment',
            'page_load_time' => time() - 1,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'status' => 'spam',
        ]);
    }

    public function test_comment_with_filled_honeypot_is_marked_as_spam(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->post(route('comments.store'), [
            'post_id' => $post->id,
            'author_name' => 'Bot',
            'author_email' => 'bot@example.com',
            'content' => 'Bot comment',
            'page_load_time' => time() - 10,
            'honeypot' => 'bot filled this',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'status' => 'spam',
        ]);
    }

    public function test_comment_rate_limiting_works(): void
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

            $response->assertRedirect();
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

        $response->assertStatus(429); // Too Many Requests
    }
}
