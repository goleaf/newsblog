<?php

namespace Tests\Feature;

use App\Jobs\SendCommentReplyNotification;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommentReplyTest extends TestCase
{
    use RefreshDatabase;

    protected Post $post;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->post = Post::factory()->published()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function it_can_create_a_reply_to_a_comment()
    {
        // Create a parent comment
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
        ]);

        // Submit a reply
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
            'author_name' => 'Reply Author',
            'author_email' => 'reply@example.com',
            'content' => 'This is a reply to the comment',
            'honeypot' => '',
            'page_load_time' => time() - 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
            'author_name' => 'Reply Author',
            'content' => 'This is a reply to the comment',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_dispatches_notification_job_when_reply_is_created()
    {
        Queue::fake();

        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
        ]);

        $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
            'author_name' => 'Reply Author',
            'author_email' => 'reply@example.com',
            'content' => 'This is a reply',
            'honeypot' => '',
            'page_load_time' => time() - 5,
        ]);

        Queue::assertPushed(SendCommentReplyNotification::class);
    }

    /** @test */
    public function it_does_not_dispatch_notification_for_spam_replies()
    {
        Queue::fake();

        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
        ]);

        // Submit a reply with spam characteristics (too many links)
        $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
            'author_name' => 'Spammer',
            'author_email' => 'spam@example.com',
            'content' => 'Check out http://spam1.com http://spam2.com http://spam3.com http://spam4.com',
            'honeypot' => '',
            'page_load_time' => time() - 5,
        ]);

        Queue::assertNotPushed(SendCommentReplyNotification::class);
    }

    /** @test */
    public function it_calculates_comment_depth_correctly()
    {
        $level1 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => null,
        ]);

        $level2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $level1->id,
        ]);

        $level3 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $level2->id,
        ]);

        $this->assertEquals(0, $level1->depth());
        $this->assertEquals(1, $level2->depth());
        $this->assertEquals(2, $level3->depth());
    }

    /** @test */
    public function it_prevents_nesting_beyond_3_levels()
    {
        // Level 1 - depth 0
        $level1 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => null,
        ]);

        // Level 2 - depth 1
        $level2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $level1->id,
        ]);

        // Level 3 - depth 2 (maximum allowed)
        $level3 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $level2->id,
        ]);

        // Level 1 and 2 can receive replies, Level 3 cannot
        $this->assertTrue($level1->canReply());
        $this->assertTrue($level2->canReply());
        $this->assertFalse($level3->canReply());
    }

    /** @test */
    public function it_loads_nested_comments_correctly()
    {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
        ]);

        $reply1 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $parentComment->id,
        ]);

        $reply2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $parentComment->id,
        ]);

        $nestedReply = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $reply1->id,
        ]);

        $parentComment->load('replies.replies');

        $this->assertCount(2, $parentComment->replies);
        $this->assertCount(1, $reply1->replies);
        $this->assertCount(0, $reply2->replies);
    }

    /** @test */
    public function it_displays_nested_comments_on_post_page()
    {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
            'author_name' => 'Parent Author',
            'content' => 'Parent comment content',
        ]);

        $reply = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $parentComment->id,
            'author_name' => 'Reply Author',
            'content' => 'Reply comment content',
        ]);

        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertStatus(200);
        $response->assertSee('Parent Author');
        $response->assertSee('Parent comment content');
        $response->assertSee('Reply Author');
        $response->assertSee('Reply comment content');
        $response->assertSee('Replying to Parent Author');
    }

    /** @test */
    public function it_shows_reply_button_only_for_comments_that_can_reply()
    {
        // Level 1 - depth 0
        $level1 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
        ]);

        // Level 2 - depth 1
        $level2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $level1->id,
        ]);

        // Level 3 - depth 2 (maximum, cannot receive replies)
        $level3 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $level2->id,
        ]);

        // Refresh to ensure parent relationships are loaded
        $level1->refresh();
        $level2->refresh();
        $level3->refresh();

        // Verify canReply logic
        $this->assertTrue($level1->canReply());
        $this->assertTrue($level2->canReply());
        $this->assertFalse($level3->canReply());

        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertStatus(200);

        // Level 1 and 2 should have reply buttons, level 3 should not
        $content = $response->getContent();

        // Check for reply buttons (the @click attribute on the button)
        $this->assertStringContainsString('@click="replyingTo = replyingTo === '.$level1->id.' ? null : '.$level1->id.'"', $content);
        $this->assertStringContainsString('@click="replyingTo = replyingTo === '.$level2->id.' ? null : '.$level2->id.'"', $content);
        $this->assertStringNotContainsString('@click="replyingTo = replyingTo === '.$level3->id.' ? null : '.$level3->id.'"', $content);
    }
}
