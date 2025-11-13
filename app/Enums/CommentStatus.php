<?php

namespace App\Enums;

enum CommentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
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
            self::Spam => 'Spam',
        };
    }
}
