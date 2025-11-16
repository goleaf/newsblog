<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Author = 'author';
    case User = 'user';

    /**
     * Get all role values as an array for Nova filters.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'Admin' => self::Admin->value,
            'Editor' => self::Editor->value,
            'Author' => self::Author->value,
            'User' => self::User->value,
        ];
    }

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Editor => 'Editor',
            self::Author => 'Author',
            self::User => 'User',
        };
    }
}

