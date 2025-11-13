<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Import Chunk Size
    |--------------------------------------------------------------------------
    |
    | The number of rows to process in each chunk during bulk import.
    | Larger chunks are faster but use more memory.
    |
    */

    'chunk_size' => env('IMPORT_CHUNK_SIZE', 1000),

    /*
    |--------------------------------------------------------------------------
    | Queue Threshold
    |--------------------------------------------------------------------------
    |
    | When the import exceeds this number of records, it will be dispatched
    | to a queue job for background processing.
    |
    */

    'queue_threshold' => env('IMPORT_QUEUE_THRESHOLD', 50000),

    /*
    |--------------------------------------------------------------------------
    | Default User ID
    |--------------------------------------------------------------------------
    |
    | The default user ID to assign to imported posts when not specified.
    |
    */

    'default_user_id' => env('IMPORT_DEFAULT_USER_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Default Status
    |--------------------------------------------------------------------------
    |
    | The default publication status for imported posts.
    | Options: draft, published, scheduled
    |
    */

    'default_status' => env('IMPORT_DEFAULT_STATUS', 'published'),

    /*
    |--------------------------------------------------------------------------
    | Content Generation Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic content generation for imported articles.
    |
    */

    'content_generation' => [
        'enabled' => env('IMPORT_GENERATE_CONTENT', true),
        'min_words' => 500,
        'max_words' => 1500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Generation Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic image assignment for imported articles.
    |
    */

    'image_generation' => [
        'enabled' => env('IMPORT_GENERATE_IMAGES', true),
        'service' => env('IMPORT_IMAGE_SERVICE', 'unsplash'), // unsplash, picsum, local
        'fallback_image' => 'images/default-post.jpg',
    ],

    /*
    |--------------------------------------------------------------------------
    | CSV Parsing Settings
    |--------------------------------------------------------------------------
    |
    | Configure CSV file parsing options.
    |
    */

    'csv' => [
        'delimiter' => ',',
        'enclosure' => '"',
        'escape' => '\\',
    ],

];
