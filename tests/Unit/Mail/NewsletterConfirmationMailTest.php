<?php

namespace Tests\Unit\Mail;

use App\Mail\NewsletterConfirmationMail;
use App\Models\Newsletter;
use Tests\TestCase;

class NewsletterConfirmationMailTest extends TestCase
{
    public function test_envelope_subject_matches_confirmation_copy(): void
    {
        $newsletter = new Newsletter;

        $mail = new NewsletterConfirmationMail($newsletter);

        $envelope = $mail->envelope();

        $this->assertSame('Newsletter Subscription Confirmed', $envelope->subject);
    }

    public function test_content_points_to_confirmation_view(): void
    {
        $newsletter = new Newsletter;

        $mail = new NewsletterConfirmationMail($newsletter);

        $content = $mail->content();

        $this->assertSame('emails.newsletter-confirmation', $content->view);
    }

    public function test_attachments_are_empty(): void
    {
        $newsletter = new Newsletter;

        $mail = new NewsletterConfirmationMail($newsletter);

        $this->assertSame([], $mail->attachments());
    }
}
