<?php

namespace App\Enums;

enum UserRole: string
{
    case Reader = 'reader';
    case Author = 'author';
    case Moderator = 'moderator';
    case Admin = 'admin';
    case Editor = 'editor';
    case User = 'user';

    /**
     * Get all role values as an array for Nova filters.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'Reader' => self::Reader->value,
            'Author' => self::Author->value,
            'Moderator' => self::Moderator->value,
            'Admin' => self::Admin->value,
            'Editor' => self::Editor->value,
            'User' => self::User->value,
        ];
    }

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Reader => 'Reader',
            self::Author => 'Author',
            self::Moderator => 'Moderator',
            self::Admin => 'Admin',
            self::Editor => 'Editor',
            self::User => 'User',
        };
    }

    /**
     * Check if the role can create articles.
     */
    public function canCreateArticles(): bool
    {
        return match ($this) {
            self::Author, self::Editor, self::Admin => true,
            default => false,
        };
    }

    /**
     * Check if the role can publish articles.
     */
    public function canPublishArticles(): bool
    {
        return match ($this) {
            self::Editor, self::Admin => true,
            default => false,
        };
    }

    /**
     * Check if the role can moderate content.
     */
    public function canModerate(): bool
    {
        return match ($this) {
            self::Moderator, self::Admin => true,
            default => false,
        };
    }

    /**
     * Check if the role has admin privileges.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Check if the role can delete any content.
     */
    public function canDeleteAnyContent(): bool
    {
        return match ($this) {
            self::Admin => true,
            default => false,
        };
    }

    /**
     * Check if the role can manage users.
     */
    public function canManageUsers(): bool
    {
        return match ($this) {
            self::Admin => true,
            default => false,
        };
    }
}

