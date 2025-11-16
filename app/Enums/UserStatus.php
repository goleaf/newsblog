<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';
    case Deleted = 'deleted';

    /**
     * Get all status values as an array for Nova filters.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'Active' => self::Active->value,
            'Suspended' => self::Suspended->value,
            'Inactive' => self::Inactive->value,
            'Deleted' => self::Deleted->value,
        ];
    }

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Inactive => 'Inactive',
            self::Deleted => 'Deleted',
        };
    }
}
