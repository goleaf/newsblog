<?php

namespace App\Enums;

enum ModerationReason: string
{
    case Spam = 'spam';
    case Offensive = 'offensive';
    case OffTopic = 'off_topic';
    case ProhibitedContent = 'prohibited_content';
    case LowQuality = 'low_quality';
    case Harassment = 'harassment';
    case Misinformation = 'misinformation';
    case Copyright = 'copyright';
    case Other = 'other';

    /**
     * Get all moderation reason values as an array.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'Spam' => self::Spam->value,
            'Offensive' => self::Offensive->value,
            'Off Topic' => self::OffTopic->value,
            'Prohibited Content' => self::ProhibitedContent->value,
            'Low Quality' => self::LowQuality->value,
            'Harassment' => self::Harassment->value,
            'Misinformation' => self::Misinformation->value,
            'Copyright' => self::Copyright->value,
            'Other' => self::Other->value,
        ];
    }

    /**
     * Get the display label for the moderation reason.
     */
    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam',
            self::Offensive => 'Offensive',
            self::OffTopic => 'Off Topic',
            self::ProhibitedContent => 'Prohibited Content',
            self::LowQuality => 'Low Quality',
            self::Harassment => 'Harassment',
            self::Misinformation => 'Misinformation',
            self::Copyright => 'Copyright',
            self::Other => 'Other',
        };
    }

    /**
     * Get the description for the moderation reason.
     */
    public function description(): string
    {
        return match ($this) {
            self::Spam => 'Unsolicited or repetitive content',
            self::Offensive => 'Contains offensive or inappropriate language',
            self::OffTopic => 'Not relevant to the discussion',
            self::ProhibitedContent => 'Contains prohibited words or content',
            self::LowQuality => 'Does not meet quality standards',
            self::Harassment => 'Harassing or bullying behavior',
            self::Misinformation => 'Contains false or misleading information',
            self::Copyright => 'Copyright infringement',
            self::Other => 'Other reason',
        };
    }

    /**
     * Check if this reason should result in automatic rejection.
     */
    public function shouldAutoReject(): bool
    {
        return match ($this) {
            self::Spam,
            self::ProhibitedContent,
            self::Harassment => true,
            default => false,
        };
    }

    /**
     * Get the severity level of the moderation reason.
     */
    public function severity(): string
    {
        return match ($this) {
            self::Spam,
            self::Offensive,
            self::ProhibitedContent,
            self::Harassment => 'high',
            self::OffTopic,
            self::LowQuality,
            self::Misinformation => 'medium',
            self::Copyright,
            self::Other => 'low',
        };
    }
}
