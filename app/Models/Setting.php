<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

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
        });

        static::deleted(function (Setting $setting): void {
            Cache::forget("setting_{$setting->key}");
        });
    }

    public static function get($key, $default = null)
    {
        return Cache::remember("setting_{$key}", 86400, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set($key, $value, $group = 'general')
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("setting_{$key}");

        return $setting;
    }
}
