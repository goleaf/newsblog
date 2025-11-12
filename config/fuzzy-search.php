<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fuzzy Search Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable fuzzy search globally or per context.
    |
    */
    'enabled' => [
        'posts' => env('FUZZY_SEARCH_POSTS', true),
        'tags' => env('FUZZY_SEARCH_TAGS', true),
        'categories' => env('FUZZY_SEARCH_CATEGORIES', true),
        'admin' => env('FUZZY_SEARCH_ADMIN', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum relevance score (0-100) for results to be included.
    | Lower values return more results but with lower relevance.
    |
    */
    'threshold' => env('FUZZY_SEARCH_THRESHOLD', 60),

    /*
    |--------------------------------------------------------------------------
    | Levenshtein Distance
    |--------------------------------------------------------------------------
    |
    | Maximum number of character edits allowed for fuzzy matching.
    | Higher values allow more typos but may return less relevant results.
    |
    */
    'levenshtein_distance' => env('FUZZY_SEARCH_LEVENSHTEIN', 2),

    /*
    |--------------------------------------------------------------------------
    | Phonetic Matching
    |--------------------------------------------------------------------------
    |
    | Enable phonetic matching using Metaphone algorithm.
    |
    */
    'phonetic_enabled' => env('FUZZY_SEARCH_PHONETIC', false),

    /*
    |--------------------------------------------------------------------------
    | Field Weights
    |--------------------------------------------------------------------------
    |
    | Weight multipliers for different fields in multi-field search.
    |
    */
    'weights' => [
        'title' => 3.0,
        'excerpt' => 2.0,
        'content' => 1.0,
        'tags' => 1.5,
        'category' => 1.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for search indexes and results.
    |
    */
    'cache' => [
        'enabled' => env('FUZZY_SEARCH_CACHE', true),
        'ttl' => env('FUZZY_SEARCH_CACHE_TTL', 600), // 10 minutes
        'index_ttl' => env('FUZZY_SEARCH_INDEX_TTL', 86400), // 24 hours
        'prefix' => 'fuzzy_search',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Limits
    |--------------------------------------------------------------------------
    |
    | Limits to prevent performance degradation.
    |
    */
    'limits' => [
        'max_query_length' => 200,
        'max_results' => 100,
        'max_index_items' => 10000,
        'suggestion_min_length' => 3,
        'suggestion_max_results' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    |
    | Search analytics and logging configuration.
    |
    */
    'analytics' => [
        'enabled' => env('FUZZY_SEARCH_ANALYTICS', true),
        'log_queries' => true,
        'log_clicks' => true,
        'archive_after_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Highlighting
    |--------------------------------------------------------------------------
    |
    | Configuration for search result highlighting.
    |
    */
    'highlighting' => [
        'enabled' => true,
        'tag' => 'mark',
        'class' => 'search-highlight',
        'context_length' => 200,
    ],
];
