<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class SettingsService
{
    /**
     * Supported simple types for settings validation.
     *
     * @var array<string, callable(mixed): bool>
     */
    private array $validators;

    /**
     * Map of key => expected type.
     * Add keys here to enforce stricter validation.
     *
     * @var array<string, string>
     */
    private array $keyTypeMap = [
        // General
        'site_name' => 'string',
        'site_description' => 'string',
        'posts_per_page' => 'int',
        // SEO
        'meta_title' => 'string',
        'meta_description' => 'string',
        'meta_keywords' => 'string',
        // Social
        'facebook_url' => 'url',
        'twitter_url' => 'url',
        'linkedin_url' => 'url',
        'github_url' => 'url',
        // Email
        'admin_email' => 'email',
        'mail_from_name' => 'string',
        'mail_from_address' => 'email',
        // Comments
        'comments_enabled' => 'bool',
        'comments_require_approval' => 'bool',
        'comments_max_depth' => 'int',
        // Media
        'max_upload_size' => 'int',
        'allowed_file_types' => 'string',
        'image_quality' => 'int',
        // Reading
        'reading_words_per_minute' => 'int',
        'show_reading_time' => 'bool',
        'show_related_posts' => 'bool',
        // Appearance
        'theme_color' => 'string',
        'dark_mode_enabled' => 'bool',
        'footer_text' => 'string',
    ];

    public function __construct()
    {
        $this->validators = [
            'string' => function (mixed $v): bool {
                return is_string($v);
            },
            'int' => function (mixed $v): bool {
                return filter_var($v, FILTER_VALIDATE_INT) !== false || is_int($v);
            },
            'bool' => function (mixed $v): bool {
                return is_bool($v) || in_array($v, [0, 1, '0', '1', 'true', 'false'], true);
            },
            'array' => function (mixed $v): bool {
                return is_array($v);
            },
            'email' => function (mixed $v): bool {
                return is_string($v) && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
            },
            'url' => function (mixed $v): bool {
                return is_string($v) && filter_var($v, FILTER_VALIDATE_URL) !== false;
            },
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_{$key}", Setting::CACHE_TTL, fn () => Setting::query()
            ->where('key', $key)
            ->value('value') ?? $default);
    }

    public function set(string $key, mixed $value, string $group = 'general'): Setting
    {
        $this->assertValid($key, $value);

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        $this->clearCache($key, $group);

        return $setting;
    }

    /**
     * Get all settings for a group as key => value.
     *
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array
    {
        return Cache::remember("settings_group_{$group}", Setting::CACHE_TTL, fn () => Setting::query()
            ->where('group', $group)
            ->pluck('value', 'key')
            ->toArray());
    }

    /**
     * Set multiple settings, optionally constrained to a group.
     *
     * @param  array<string, mixed>  $settings
     */
    public function setMany(array $settings, string $group = 'general'): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    public function clearAllCache(): void
    {
        Cache::forget('settings_all');

        foreach (array_keys(Setting::GROUPS) as $group) {
            Cache::forget("settings_group_{$group}");
        }

        Setting::query()->pluck('key')->each(fn ($key) => Cache::forget("setting_{$key}"));
    }

    private function assertValid(string $key, mixed &$value): void
    {
        $expectedType = Arr::get($this->keyTypeMap, $key);
        if (! $expectedType) {
            return;
        }

        // Coerce common scalar forms where appropriate
        if ($expectedType === 'bool' && is_string($value)) {
            $normalized = strtolower($value);
            if (in_array($normalized, ['1', 'true'], true)) {
                $value = true;
            } elseif (in_array($normalized, ['0', 'false'], true)) {
                $value = false;
            }
        }

        if ($expectedType === 'int' && is_string($value) && is_numeric($value)) {
            $value = (int) $value;
        }

        $validator = $this->validators[$expectedType] ?? null;
        if ($validator && ! $validator($value)) {
            throw new InvalidArgumentException("Invalid value for setting '{$key}'; expected {$expectedType}.");
        }
    }

    private function clearCache(string $key, string $group): void
    {
        Cache::forget("setting_{$key}");
        Cache::forget("settings_group_{$group}");
        Cache::forget('settings_all');
    }
}
