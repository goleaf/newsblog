<?php

namespace Tests\Unit\Mail;

use App\Mail\CommentApprovedMail;
use App\Models\Comment;
use Tests\TestCase;

class CommentApprovedMailTest extends TestCase
{
    public function test_envelope_subject_acknowledges_approval(): void
    {
        $comment = new Comment;

        $mail = new CommentApprovedMail($comment);

        $envelope = $mail->envelope();

        $this->assertSame('Your comment has been approved', $envelope->subject);
    }

    public function test_content_view_points_to_comment_approved_template(): void
    {
        $comment = new Comment;

        $mail = new CommentApprovedMail($comment);

        $content = $mail->content();

        $this->assertSame('emails.comment-approved', $content->view);
    }

    public function test_attachments_are_empty(): void
    {
        $comment = new Comment;

        $mail = new CommentApprovedMail($comment);

        $this->assertSame([], $mail->attachments());
    }
}
