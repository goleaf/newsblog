<?php

namespace Tests\Unit\Mail;

use App\Mail\CommentReplyMail;
use App\Models\Comment;
use Tests\TestCase;

class CommentReplyMailTest extends TestCase
{
    public function test_envelope_subject_mentions_reply(): void
    {
        $reply = new Comment;
        $parent = new Comment;

        $mail = new CommentReplyMail($reply, $parent);

        $envelope = $mail->envelope();

        $this->assertSame('Someone replied to your comment', $envelope->subject);
    }

    public function test_content_view_points_to_reply_template(): void
    {
        $reply = new Comment;
        $parent = new Comment;

        $mail = new CommentReplyMail($reply, $parent);

        $content = $mail->content();

        $this->assertSame('emails.comment-reply', $content->view);
    }

    public function test_attachments_are_empty(): void
    {
        $reply = new Comment;
        $parent = new Comment;

        $mail = new CommentReplyMail($reply, $parent);

        $this->assertSame([], $mail->attachments());
    }
}
