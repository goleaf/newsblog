<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSend extends Model
{
    use HasFactory;

    protected $fillable = [
        'newsletter_id', // reference to Newsletter subscription or campaign id as appropriate
        'subject',
        'status', // queued, sending, sent, failed
        'sent_at',
        'provider_message_id',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
