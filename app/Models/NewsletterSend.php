<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSend extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscriber_id', // references newsletters.id
        'batch_id',
        'subject',
        'content',
        'status', // queued, sending, sent, failed
        'sent_at',
        'opened_at',
        'clicked_at',
        'click_count',
        'clicked_links',
        'provider_message_id',
        'error',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'clicked_links' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * Check if newsletter was opened.
     */
    public function wasOpened(): bool
    {
        return $this->opened_at !== null;
    }

    /**
     * Check if any link was clicked.
     */
    public function wasClicked(): bool
    {
        return $this->clicked_at !== null;
    }

    /**
     * Get engagement rate (0-100).
     */
    public function getEngagementRate(): float
    {
        if (! $this->wasOpened()) {
            return 0;
        }

        if ($this->wasClicked()) {
            return 100;
        }

        return 50; // Opened but not clicked
    }
}
