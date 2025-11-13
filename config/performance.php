<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Critical CSS Configuration
    |--------------------------------------------------------------------------
    |
    | Configure critical CSS paths and settings for performance optimization.
    |
    */

    'critical_css' => [
        'enabled' => env('CRITICAL_CSS_ENABLED', true),

        // Path to critical CSS file (generated separately)
        'path' => public_path('build/critical.css'),

        // Maximum size for critical CSS (in bytes)
        'max_size' => 14336, // 14KB recommended
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Optimization
    |--------------------------------------------------------------------------
    |
    | Configure asset optimization settings.
    |
    */

    'assets' => [
        // Cache duration for static assets (in seconds)
        'cache_duration' => 31536000, // 1 year

        // Enable lazy loading for images
        'lazy_loading' => true,

        // Preload critical assets
        'preload' => [
            'fonts' => true,
            'images' => false,
        ],
    ],
];
