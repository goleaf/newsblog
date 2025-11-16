<?php

return [

    // Driver: database|meilisearch|algolia (we default to database if not set)
    'driver' => env('SCOUT_DRIVER', 'database'),

    // Optional index prefix for multi-tenant / environments
    'prefix' => env('SCOUT_PREFIX', ''),

    // Queue index updates for performance
    'queue' => (bool) env('SCOUT_QUEUE', false),

    // Only index models after DB commit
    'after_commit' => (bool) env('SCOUT_AFTER_COMMIT', false),

    // Chunk sizes for batch import
    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    // Include soft-deleted models in search
    'soft_delete' => (bool) env('SCOUT_SOFT_DELETE', false),

    // Identify the user performing search requests (some drivers support this)
    'identify' => (bool) env('SCOUT_IDENTIFY', false),

    // Algolia (not used by default)
    'algolia' => [
        'id' => env('ALGOLIA_APP_ID'),
        'secret' => env('ALGOLIA_SECRET'),
    ],

    // Meilisearch client configuration
    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY'),
    ],
];
