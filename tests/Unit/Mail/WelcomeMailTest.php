<?php

namespace Tests\Unit\Mail;

use App\Mail\WelcomeMail;
use App\Models\User;
use Tests\TestCase;

class WelcomeMailTest extends TestCase
{
    public function test_envelope_subject_uses_application_name(): void
    {
        config(['app.name' => 'Tech News Hub']);

        $user = new User;

        $mail = new WelcomeMail($user);

        $envelope = $mail->envelope();

        $this->assertSame('Welcome to Tech News Hub', $envelope->subject);
    }

    public function test_content_view_points_to_welcome_template(): void
    {
        $user = new User;

        $mail = new WelcomeMail($user);

        $content = $mail->content();

        $this->assertSame('emails.welcome', $content->view);
    }

    public function test_attachments_are_empty(): void
    {
        $user = new User;

        $mail = new WelcomeMail($user);

        $this->assertSame([], $mail->attachments());
    }
}
