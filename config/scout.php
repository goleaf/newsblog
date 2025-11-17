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

        // Index-specific settings
        'index-settings' => [
            'posts' => [
                // Searchable attributes (fields to search in)
                'searchableAttributes' => [
                    'title',
                    'content',
                    'excerpt',
                    'author',
                    'category',
                    'tags',
                ],

                // Attributes to display in search results
                'displayedAttributes' => [
                    'id',
                    'title',
                    'slug',
                    'excerpt',
                    'author',
                    'category',
                    'tags',
                    'view_count',
                    'reading_time',
                    'published_at',
                ],

                // Filterable attributes (for filtering results)
                'filterableAttributes' => [
                    'category',
                    'author',
                    'tags',
                    'published_at',
                    'view_count',
                    'reading_time',
                ],

                // Sortable attributes
                'sortableAttributes' => [
                    'published_at',
                    'view_count',
                    'reading_time',
                ],

                // Ranking rules (order matters - first rule has highest priority)
                'rankingRules' => [
                    'words',        // Number of matched query terms
                    'typo',         // Fewer typos = better rank
                    'proximity',    // Proximity of query terms
                    'attribute',    // Matches in important attributes (title > content)
                    'sort',         // Custom sort criteria
                    'exactness',    // Exact matches rank higher
                ],

                // Typo tolerance settings
                'typoTolerance' => [
                    'enabled' => true,
                    'minWordSizeForTypos' => [
                        'oneTypo' => 5,   // Allow 1 typo for words >= 5 chars
                        'twoTypos' => 9,  // Allow 2 typos for words >= 9 chars
                    ],
                ],

                // Synonyms for better search results
                'synonyms' => [
                    'js' => ['javascript'],
                    'javascript' => ['js'],
                    'ts' => ['typescript'],
                    'typescript' => ['ts'],
                    'py' => ['python'],
                    'python' => ['py'],
                    'ai' => ['artificial intelligence', 'machine learning'],
                    'ml' => ['machine learning'],
                    'api' => ['application programming interface'],
                    'db' => ['database'],
                    'frontend' => ['front-end', 'front end'],
                    'backend' => ['back-end', 'back end'],
                    'fullstack' => ['full-stack', 'full stack'],
                    'devops' => ['dev ops'],
                    'ci/cd' => ['continuous integration', 'continuous deployment'],
                ],

                // Stop words (common words to ignore)
                'stopWords' => [
                    'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
                ],

                // Pagination settings
                'pagination' => [
                    'maxTotalHits' => 1000,
                ],
            ],
        ],
    ],
];
