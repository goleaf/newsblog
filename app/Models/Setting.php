<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    /**
     * Cache TTL in seconds (24 hours).
     */
    public const CACHE_TTL = 86400;

    /**
     * Available setting groups.
     */
    public const GROUPS = [
        'general' => 'General',
        'seo' => 'SEO',
        'social' => 'Social Media',
        'email' => 'Email',
        'comments' => 'Comments',
        'media' => 'Media',
        'features' => 'Feature Flags',
        'api' => 'API Settings',
    ];

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (Setting $setting): void {
            Cache::forget("setting_{$setting->key}");
            Cache::forget("settings_group_{$setting->group}");
            Cache::forget('settings_all');
        });

        static::deleted(function (Setting $setting): void {
            Cache::forget("setting_{$setting->key}");
            Cache::forget("settings_group_{$setting->group}");
            Cache::forget('settings_all');
        });
    }

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_{$key}", self::CACHE_TTL, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        self::clearCache($key);

        return $setting;
    }

    /**
     * Get all settings as key-value pairs.
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('settings_all', self::CACHE_TTL, function () {
            return self::query()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get settings by group.
     */
    public static function getByGroup(string $group): array
    {
        return Cache::remember("settings_group_{$group}", self::CACHE_TTL, function () use ($group) {
            return self::where('group', $group)->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Set multiple settings at once.
     */
    public static function setMany(array $settings, string $group = 'general'): void
    {
        foreach ($settings as $key => $value) {
            self::set($key, $value, $group);
        }
    }

    /**
     * Check if a setting exists.
     */
    public static function has(string $key): bool
    {
        return self::where('key', $key)->exists();
    }

    /**
     * Clear cache for a specific setting.
     */
    public static function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget("setting_{$key}");
            $setting = self::where('key', $key)->first();
            if ($setting) {
                Cache::forget("settings_group_{$setting->group}");
            }
        }

        Cache::forget('settings_all');
    }

    /**
     * Clear all settings cache.
     */
    public static function clearAllCache(): void
    {
        $groups = array_keys(self::GROUPS);
        foreach ($groups as $group) {
            Cache::forget("settings_group_{$group}");
        }

        Cache::forget('settings_all');

        // Clear individual setting caches
        self::query()->pluck('key')->each(function ($key) {
            Cache::forget("setting_{$key}");
        });
    }
}
