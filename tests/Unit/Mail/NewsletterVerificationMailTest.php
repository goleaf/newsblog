<?php

namespace Tests\Unit\Mail;

use App\Mail\NewsletterVerificationMail;
use App\Models\Newsletter;
use Tests\TestCase;

class NewsletterVerificationMailTest extends TestCase
{
    public function test_envelope_subject_requests_verification(): void
    {
        $newsletter = new Newsletter;

        $mail = new NewsletterVerificationMail($newsletter);

        $envelope = $mail->envelope();

        $this->assertSame('Verify Your Newsletter Subscription', $envelope->subject);
    }

    public function test_content_view_matches_verification_template(): void
    {
        $newsletter = new Newsletter;

        $mail = new NewsletterVerificationMail($newsletter);

        $content = $mail->content();

        $this->assertSame('emails.newsletter-verification', $content->view);
    }

    public function test_attachments_are_empty(): void
    {
        $newsletter = new Newsletter;

        $mail = new NewsletterVerificationMail($newsletter);

        $this->assertSame([], $mail->attachments());
    }
}
