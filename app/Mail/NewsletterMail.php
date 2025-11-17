<?php

namespace App\Mail;

use App\Models\Newsletter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class NewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    protected string $subjectText;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $subject,
        public Collection $articles,
        public Newsletter $subscriber,
        public ?string $trackingToken = null,
        public ?string $greeting = null
    ) {
        $this->subjectText = $subject;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectText,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter',
            with: [
                'subject' => $this->subjectText,
                'articles' => $this->articles,
                'subscriber' => $this->subscriber,
                'trackingToken' => $this->trackingToken,
                'greeting' => $this->greeting,
                'unsubscribeUrl' => route('newsletter.unsubscribe', $this->subscriber->unsubscribe_token),
                'preferencesUrl' => route('newsletter.preferences', $this->subscriber->unsubscribe_token),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
