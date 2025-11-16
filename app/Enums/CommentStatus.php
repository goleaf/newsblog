<?php

namespace App\Enums;

enum CommentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Flagged = 'flagged';
    case Spam = 'spam';

    /**
     * Get all status values as an array for Nova filters.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'Pending' => self::Pending->value,
            'Approved' => self::Approved->value,
            'Rejected' => self::Rejected->value,
            'Flagged' => self::Flagged->value,
            'Spam' => self::Spam->value,
        ];
    }

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Flagged => 'Flagged',
            self::Spam => 'Spam',
        };
    }
}
