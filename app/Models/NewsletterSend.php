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
            'meta' => 'array',
        ];
    }
}
