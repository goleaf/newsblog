<?php

namespace App\Enums;

/**
 * ArticleStatus enum - Alias for PostStatus to maintain consistency with requirements.
 * The platform uses "Post" terminology in the codebase but "Article" in specifications.
 */
enum ArticleStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * Get all status values as an array.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'Draft' => self::Draft->value,
            'Scheduled' => self::Scheduled->value,
            'Published' => self::Published->value,
            'Archived' => self::Archived->value,
        ];
    }

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    /**
     * Check if the article is publicly visible.
     */
    public function isPublic(): bool
    {
        return $this === self::Published;
    }

    /**
     * Check if the article can be edited.
     */
    public function isEditable(): bool
    {
        return match ($this) {
            self::Draft, self::Scheduled => true,
            default => false,
        };
    }
}
