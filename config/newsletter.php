<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Newsletter Sending Schedule
    |--------------------------------------------------------------------------
    |
    | Configure when newsletters should be sent for each frequency.
    | Times are in 24-hour format (0-23).
    | Days: 0 = Sunday, 1 = Monday, ..., 6 = Saturday
    |
    */

    'daily_send_hour' => env('NEWSLETTER_DAILY_HOUR', 8),

    'weekly_send_day' => env('NEWSLETTER_WEEKLY_DAY', 1), // Monday
    'weekly_send_hour' => env('NEWSLETTER_WEEKLY_HOUR', 8),

    'monthly_send_day' => env('NEWSLETTER_MONTHLY_DAY', 1), // 1st of month
    'monthly_send_hour' => env('NEWSLETTER_MONTHLY_HOUR', 8),

    /*
    |--------------------------------------------------------------------------
    | Newsletter Content Settings
    |--------------------------------------------------------------------------
    |
    | Configure how newsletter content is selected and generated.
    |
    */

    'articles_per_newsletter' => env('NEWSLETTER_ARTICLES_COUNT', 10),

    'engagement_weights' => [
        'views' => 1,
        'comments' => 5,
        'bookmarks' => 3,
        'reactions' => 2,
        'shares' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for sending newsletters to avoid overwhelming
    | your email provider.
    |
    */

    'rate_limit_per_second' => env('NEWSLETTER_RATE_LIMIT', 10),

    /*
    |--------------------------------------------------------------------------
    | Batch Settings
    |--------------------------------------------------------------------------
    |
    | Configure batch processing settings for newsletter sending.
    |
    */

    'batch_size' => env('NEWSLETTER_BATCH_SIZE', 100),

];
