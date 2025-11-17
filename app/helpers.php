<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    /**
     * Get a setting value by key.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('settings')) {
    /**
     * Get all settings as key-value pairs.
     */
    function settings(): array
    {
        return Setting::getAllSettings();
    }
}

if (! function_exists('responsive_image_url')) {
    /**
     * Generate a responsive image URL with CDN support.
     */
    function responsive_image_url(string $path, string $size = 'medium'): string
    {
        $cdnUrl = config('app.cdn_url');
        $baseUrl = $cdnUrl ?: config('app.url');

        // Remove 'public/' prefix if present
        $path = str_replace('public/', '', $path);

        return rtrim($baseUrl, '/').'/storage/'.$path;
    }
}

if (! function_exists('responsive_image_srcset')) {
    /**
     * Generate srcset attribute for responsive images.
     */
    function responsive_image_srcset(array $variants): string
    {
        $srcset = [];

        foreach ($variants as $size => $info) {
            if (is_array($info) && isset($info['path'], $info['width'])) {
                $url = responsive_image_url($info['path']);
                $srcset[] = "{$url} {$info['width']}w";
            }
        }

        return implode(', ', $srcset);
    }
}
