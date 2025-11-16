<?php

namespace App\Models;

use App\Enums\NewsletterStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'status',
        'verified_at',
        'token',
        'verification_token',
        'verification_token_expires_at',
        'unsubscribe_token',
        'unsubscribed_at',
    ];

    protected $casts = [
        'status' => NewsletterStatus::class,
        'verified_at' => 'datetime',
        'verification_token_expires_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function scopeSubscribed($query)
    {
        return $query->where('status', NewsletterStatus::Subscribed->value);
    }

    public function scopeUnsubscribed($query)
    {
        return $query->where('status', NewsletterStatus::Unsubscribed->value);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function verify(): void
    {
        $this->update([
            'status' => NewsletterStatus::Subscribed,
            'verified_at' => now(),
            'verification_token' => null,
            'verification_token_expires_at' => null,
        ]);
    }

    public function unsubscribe(): void
    {
        $this->update([
            'status' => NewsletterStatus::Unsubscribed,
            'unsubscribed_at' => now(),
        ]);
    }

    public function isVerificationTokenValid(): bool
    {
        return $this->verification_token !== null
            && $this->verification_token_expires_at !== null
            && $this->verification_token_expires_at->isFuture();
    }

    public static function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function generateUnsubscribeToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
