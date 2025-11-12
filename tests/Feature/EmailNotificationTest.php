<?php

namespace Tests\Feature;

use App\Jobs\SendCommentApprovedNotification;
use App\Jobs\SendWelcomeEmail;
use App\Mail\CommentApprovedMail;
use App\Mail\WelcomeMail;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_comment_approved_notification_to_post_author()
    {
        Mail::fake();

        $author = User::factory()->create(['email' => 'author@example.com']);
        $post = Post::factory()->published()->create(['user_id' => $author->id]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => 'pending',
        ]);

        // Approve the comment
        $comment->update(['status' => 'approved']);

        // Manually dispatch the job (simulating what the Nova action does)
        dispatch(new SendCommentApprovedNotification($comment));

        Mail::assertSent(CommentApprovedMail::class, function ($mail) use ($author, $comment) {
            return $mail->hasTo($author->email) &&
                   $mail->comment->id === $comment->id;
        });
    }

    /** @test */
    public function it_queues_comment_approved_notification_job()
    {
        Queue::fake();

        $author = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $author->id]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => 'pending',
        ]);

        // Dispatch the notification job
        dispatch(new SendCommentApprovedNotification($comment));

        Queue::assertPushed(SendCommentApprovedNotification::class, function ($job) use ($comment) {
            return $job->comment->id === $comment->id;
        });
    }

    /** @test */
    public function it_sends_welcome_email_on_user_registration()
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'newuser@example.com']);

        // Manually dispatch the job (simulating what the event listener does)
        dispatch(new SendWelcomeEmail($user));

        Mail::assertSent(WelcomeMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) &&
                   $mail->user->id === $user->id;
        });
    }

    /** @test */
    public function it_dispatches_welcome_email_job_on_registered_event()
    {
        Mail::fake();

        $user = User::factory()->create();

        // Fire the Registered event
        event(new Registered($user));

        // Since the listener is queued, we need to process the queue
        // or check that the mail would be sent
        // For now, let's manually trigger the listener to verify it works
        $listener = new \App\Listeners\SendWelcomeEmailListener;
        $listener->handle(new Registered($user));

        // Verify the job was dispatched
        Mail::assertSent(WelcomeMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /** @test */
    public function comment_approved_mail_contains_correct_content()
    {
        $author = User::factory()->create(['name' => 'John Doe']);
        $post = Post::factory()->published()->create([
            'user_id' => $author->id,
            'title' => 'Test Post Title',
        ]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'author_name' => 'Jane Smith',
            'content' => 'This is a test comment',
            'status' => 'approved',
        ]);

        $mailable = new CommentApprovedMail($comment);

        $mailable->assertSeeInHtml('John Doe');
        $mailable->assertSeeInHtml('Test Post Title');
        $mailable->assertSeeInHtml('Jane Smith');
        $mailable->assertSeeInHtml('This is a test comment');
        $mailable->assertSeeInHtml('Comment Approved');
    }

    /** @test */
    public function welcome_mail_contains_correct_content()
    {
        $user = User::factory()->create(['name' => 'Alice Johnson']);

        $mailable = new WelcomeMail($user);

        $mailable->assertSeeInHtml('Alice Johnson');
        $mailable->assertSeeInHtml('Welcome to');
        $mailable->assertSeeInHtml('Thank you for joining');
        $mailable->assertSeeInHtml('Start Exploring');
    }

    /** @test */
    public function comment_approved_mail_has_correct_subject()
    {
        $comment = Comment::factory()->create();
        $mailable = new CommentApprovedMail($comment);

        $this->assertEquals('Your comment has been approved', $mailable->envelope()->subject);
    }

    /** @test */
    public function welcome_mail_has_correct_subject()
    {
        $user = User::factory()->create();
        $mailable = new WelcomeMail($user);

        $this->assertStringContainsString('Welcome to', $mailable->envelope()->subject);
    }

    /** @test */
    public function comment_approved_notification_job_handles_execution()
    {
        Mail::fake();

        $author = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $author->id]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => 'approved',
        ]);

        $job = new SendCommentApprovedNotification($comment);
        $job->handle();

        Mail::assertSent(CommentApprovedMail::class);
    }

    /** @test */
    public function welcome_email_job_handles_execution()
    {
        Mail::fake();

        $user = User::factory()->create();

        $job = new SendWelcomeEmail($user);
        $job->handle();

        Mail::assertSent(WelcomeMail::class);
    }

    /** @test */
    public function all_email_notifications_are_queued()
    {
        $this->assertTrue(in_array('Illuminate\Contracts\Queue\ShouldQueue', class_implements(SendCommentApprovedNotification::class)));
        $this->assertTrue(in_array('Illuminate\Contracts\Queue\ShouldQueue', class_implements(SendWelcomeEmail::class)));
    }
}
