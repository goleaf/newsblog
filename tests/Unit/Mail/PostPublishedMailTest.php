<?php

namespace Tests\Unit\Mail;

use App\Mail\PostPublishedMail;
use App\Models\Post;
use Tests\TestCase;

class PostPublishedMailTest extends TestCase
{
    public function test_envelope_subject_includes_post_title(): void
    {
        $post = new Post;
        $post->title = 'Localized Insights';

        $mail = new PostPublishedMail($post);

        $envelope = $mail->envelope();

        $this->assertSame('Your Post Has Been Published: Localized Insights', $envelope->subject);
    }

    public function test_content_uses_post_published_view(): void
    {
        $post = new Post;
        $post->title = 'Any Title';

        $mail = new PostPublishedMail($post);

        $content = $mail->content();

        $this->assertSame('emails.post-published', $content->view);
    }

    public function test_attachments_are_empty(): void
    {
        $post = new Post;
        $post->title = 'Any Title';

        $mail = new PostPublishedMail($post);

        $this->assertSame([], $mail->attachments());
    }
}
