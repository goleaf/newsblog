<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class BulkImportService
{
    protected CsvParserService $csvParser;

    protected NewsContentGeneratorService $contentGenerator;

    protected NewsImageGeneratorService $imageGenerator;

    protected CacheService $cacheService;

    protected SitemapService $sitemapService;

    protected array $tagCache = [];

    protected array $categoryCache = [];

    // Track slugs seen during the current import to avoid in-batch duplicates
    protected array $seenSlugs = [];

    protected array $stats = [
        'total_rows' => 0,
        'successful' => 0,
        'failed' => 0,
        'skipped' => 0,
        'posts_created' => 0,
        'tags_created' => 0,
        'categories_created' => 0,
        'content_generated' => 0,
        'images_assigned' => 0,
        'errors' => [],
    ];

    public function __construct(
        CsvParserService $csvParser,
        NewsContentGeneratorService $contentGenerator,
        NewsImageGeneratorService $imageGenerator,
        CacheService $cacheService,
        SitemapService $sitemapService
    ) {
        $this->csvParser = $csvParser;
        $this->contentGenerator = $contentGenerator;
        $this->imageGenerator = $imageGenerator;
        $this->cacheService = $cacheService;
        $this->sitemapService = $sitemapService;
    }

    /**
     * Import articles from CSV file.
     */
    public function import(string $filePath, array $options = []): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Reset seen slugs for this import run
        $this->seenSlugs = [];

        // Reset statistics for a fresh run
        $this->stats = [
            'total_rows' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'posts_created' => 0,
            'tags_created' => 0,
            'categories_created' => 0,
            'content_generated' => 0,
            'images_assigned' => 0,
            'errors' => [],
        ];

        Log::channel('import')->info('Import started', [
            'file' => $filePath,
            'options' => $options,
        ]);

        try {
            // Parse CSV file lazily
            $rows = $this->csvParser->parseLazy($filePath);
            $limit = $options['limit'] ?? null;
            if ($limit !== null) {
                $rows = $rows->take((int) $limit);
            }

            // Count total rows for progress tracking
            $totalRows = 0;
            foreach ($rows as $row) {
                $totalRows++;
            }

            // Pre-fetch and cache tags and categories
            $rows = $this->csvParser->parseLazy($filePath);
            if ($limit !== null) {
                $rows = $rows->take((int) $limit);
            }
            $this->initializeCaches($rows, $options);

            // Reset rows iterator after cache initialization
            $rows = $this->csvParser->parseLazy($filePath);
            if ($limit !== null) {
                $rows = $rows->take((int) $limit);
            }

            // Process in chunks
            $chunkSize = $options['chunk_size'] ?? config('import.chunk_size', 1000);
            $chunkNumber = 0;
            $processedRows = 0;
            $progressCallback = $options['progress_callback'] ?? null;

            $rows->chunk($chunkSize)->each(function ($chunk) use (&$chunkNumber, &$processedRows, $totalRows, $progressCallback, $options) {
                $chunkNumber++;
                $this->processChunk($chunk, $chunkNumber, $options);

                // Update progress
                $processedRows += $chunk->count();
                if ($progressCallback) {
                    $progressCallback($processedRows, $totalRows);
                }
            });

            // Calculate statistics
            $duration = microtime(true) - $startTime;
            $memoryPeak = memory_get_peak_usage() - $startMemory;
            $totalQueries = count(DB::getQueryLog());

            $this->stats['duration'] = round($duration, 2);
            $this->stats['memory_peak'] = $this->formatBytes($memoryPeak);
            $this->stats['memory_peak_bytes'] = $memoryPeak;
            $this->stats['posts_per_second'] = $duration > 0 ? round($this->stats['successful'] / $duration, 2) : 0;
            $this->stats['total_queries'] = $totalQueries;
            $this->stats['queries_per_post'] = $this->stats['successful'] > 0 ? round($totalQueries / $this->stats['successful'], 2) : 0;

            // Perform post-import operations
            $this->performPostImportOperations($options);

            Log::channel('import')->info('Import completed', $this->stats);

            return $this->stats;
        } catch (\Exception $e) {
            Log::channel('import')->error('Import failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Initialize tag and category caches.
     */
    protected function initializeCaches($rows, array $options): void
    {
        $allTagSlugs = collect();
        $allCategorySlugs = collect();

        // Collect all unique tags and categories from CSV
        foreach ($rows as $row) {
            if (! empty($row['tags'])) {
                $tags = $this->parseCommaSeparated($row['tags']);
                foreach ($tags as $tagName) {
                    $allTagSlugs->push(\Illuminate\Support\Str::slug($tagName));
                }
            }
            // Accept optional 'keywords' column as synonyms for tags
            if (! empty($row['keywords'])) {
                $keywords = $this->parseCommaSeparated($row['keywords']);
                foreach ($keywords as $kw) {
                    $allTagSlugs->push(\Illuminate\Support\Str::slug($kw));
                }
            }

            if (! empty($row['categories'])) {
                $categories = $this->parseCommaSeparated($row['categories']);
                foreach ($categories as $catName) {
                    $allCategorySlugs->push(\Illuminate\Support\Str::slug($catName));
                }
            }
        }

        $allTagSlugs = $allTagSlugs->unique()->filter();
        $allCategorySlugs = $allCategorySlugs->unique()->filter();

        // Fetch existing tags and categories
        $existingTags = Tag::whereIn('slug', $allTagSlugs)->get()->keyBy('slug');
        $existingCategories = Category::whereIn('slug', $allCategorySlugs)->get()->keyBy('slug');

        // Create missing tags
        $newTags = $allTagSlugs->diff($existingTags->keys());
        if ($newTags->isNotEmpty()) {
            $tagsToInsert = [];
            foreach ($newTags as $slug) {
                if (! $slug) {
                    continue;
                }
                // Use slug as name fallback (will be humanized when displayed)
                $tagsToInsert[] = [
                    'name' => str_replace('-', ' ', $slug),
                    'slug' => $slug,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($tagsToInsert)) {
                DB::table('tags')->insert($tagsToInsert);
                $this->stats['tags_created'] = count($tagsToInsert);
            }

            // Refresh tag cache
            $existingTags = Tag::whereIn('slug', $allTagSlugs)->get()->keyBy('slug');
        }

        // Create missing categories
        $newCategories = $allCategorySlugs->diff($existingCategories->keys());
        if ($newCategories->isNotEmpty()) {
            $categoriesToInsert = [];
            foreach ($newCategories as $slug) {
                if (! $slug) {
                    continue;
                }
                $categoriesToInsert[] = [
                    'name' => str_replace('-', ' ', $slug),
                    'slug' => $slug,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($categoriesToInsert)) {
                DB::table('categories')->insert($categoriesToInsert);
                $this->stats['categories_created'] = count($categoriesToInsert);
            }

            // Refresh category cache
            $existingCategories = Category::whereIn('slug', $allCategorySlugs)->get()->keyBy('slug');
        }

        // Build lookup maps
        $this->tagCache = $existingTags->mapWithKeys(function ($tag) {
            return [$tag->slug => $tag->id];
        })->toArray();

        $this->categoryCache = $existingCategories->mapWithKeys(function ($category) {
            return [$category->slug => $category->id];
        })->toArray();

        Log::channel('import')->info('Caches initialized', [
            'tags' => count($this->tagCache),
            'categories' => count($this->categoryCache),
            'new_tags' => $this->stats['tags_created'],
            'new_categories' => $this->stats['categories_created'],
        ]);
    }

    /**
     * Process a chunk of CSV rows.
     */
    protected function processChunk(LazyCollection|Collection $chunk, int $chunkNumber, array $options): void
    {
        $chunkStartMemory = memory_get_usage(true);

        // Avoid enabling query logging by default to reduce memory usage during large imports

        Log::channel('import')->info("Processing chunk {$chunkNumber}", [
            'rows' => $chunk->count(),
            'memory_before' => $this->formatBytes($chunkStartMemory),
        ]);

        try {
            DB::beginTransaction();

            $postsToInsert = [];
            $tagPivotData = [];
            $categoryPivotData = [];
            $rowNumber = ($chunkNumber - 1) * ($options['chunk_size'] ?? config('import.chunk_size', 1000));

            foreach ($chunk as $row) {
                $rowNumber++;
                $this->stats['total_rows']++;

                try {
                    // Prepare post data
                    $postData = $this->preparePostData($row, $options);

                    if ($postData === null) {
                        $this->stats['skipped']++;

                        continue;
                    }

                    // Guard against in-batch duplicates by slug
                    if (! empty($postData['slug']) && isset($this->seenSlugs[$postData['slug']])) {
                        $this->stats['skipped']++;

                        continue;
                    }

                    // Extract tag IDs for later pivot insertion
                    $tagIds = $postData['tag_ids'] ?? [];
                    unset($postData['tag_ids']);

                    // Extract extra categories for later pivot insertion, then remove from post data
                    $extraCategoryIdsForPivot = $postData['extra_category_ids'] ?? [];
                    unset($postData['extra_category_ids']);

                    // Queue post for bulk insert
                    $postsToInsert[] = $postData;

                    // Mark slug as seen for the remainder of this import
                    if (! empty($postData['slug'])) {
                        $this->seenSlugs[$postData['slug']] = true;
                    }

                    // Store tag relationships for later (we'll get post IDs after insert)
                    if (! empty($tagIds)) {
                        $tagPivotData[] = [
                            'post_index' => count($postsToInsert) - 1,
                            'tag_ids' => $tagIds,
                        ];
                    }

                    // Store extra category relationships (beyond primary) for later
                    if (! empty($extraCategoryIdsForPivot)) {
                        $categoryPivotData[] = [
                            'post_index' => count($postsToInsert) - 1,
                            'category_ids' => $extraCategoryIdsForPivot,
                        ];
                    }

                    $this->stats['successful']++;
                } catch (\Exception $e) {
                    $this->stats['failed']++;
                    $this->stats['errors'][] = [
                        'row' => $rowNumber,
                        'error' => $e->getMessage(),
                    ];

                    Log::channel('import')->error('Row processing failed', [
                        'row' => $rowNumber,
                        'data' => $row,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Bulk insert posts
            if (! empty($postsToInsert)) {
                // Disable model events for bulk operations
                try {
                    Post::withoutEvents(function () use ($postsToInsert, $tagPivotData, $categoryPivotData) {
                        // For SQLite, temporarily disable FK checks during bulk insert to avoid
                        // false positives when inserting many rows in a transaction.
                        $driver = DB::getDriverName();
                        $sqliteFkWasDisabled = false;
                        if ($driver === 'sqlite') {
                            try {
                                DB::statement('PRAGMA foreign_keys = OFF');
                                $sqliteFkWasDisabled = true;
                            } catch (\Throwable $e) {
                                // noop; continue
                            }
                        }

                        DB::table('posts')->insert($postsToInsert);

                        // Get the IDs of inserted posts by their slugs (reliable and memory efficient)
                        $slugs = array_column($postsToInsert, 'slug');

                        $insertedPosts = DB::table('posts')
                            ->whereIn('slug', $slugs)
                            ->pluck('id', 'slug')
                            ->toArray();

                        // Build pivot table data
                        $pivotInserts = [];
                        foreach ($tagPivotData as $pivot) {
                            $postIndex = $pivot['post_index'];
                            $slug = $postsToInsert[$postIndex]['slug'];

                            if (isset($insertedPosts[$slug])) {
                                $postId = $insertedPosts[$slug];

                                foreach ($pivot['tag_ids'] as $tagId) {
                                    $pivotInserts[] = [
                                        'post_id' => $postId,
                                        'tag_id' => $tagId,
                                    ];
                                }
                            }
                        }

                        // Bulk insert pivot relationships
                        if (! empty($pivotInserts)) {
                            DB::table('post_tag')->insert($pivotInserts);
                        }

                        // Build and insert category pivot rows (category_post)
                        $categoryInserts = [];
                        foreach ($categoryPivotData as $pivot) {
                            $postIndex = $pivot['post_index'];
                            $slug = $postsToInsert[$postIndex]['slug'];

                            if (isset($insertedPosts[$slug])) {
                                $postId = $insertedPosts[$slug];

                                foreach ($pivot['category_ids'] as $categoryId) {
                                    $categoryInserts[] = [
                                        'post_id' => $postId,
                                        'category_id' => $categoryId,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                }
                            }
                        }

                        if (! empty($categoryInserts)) {
                            DB::table('category_post')->insert($categoryInserts);
                        }

                        if ($sqliteFkWasDisabled) {
                            try {
                                DB::statement('PRAGMA foreign_keys = ON');
                            } catch (\Throwable $e) {
                                // noop
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::channel('import')->error('Bulk insert failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'posts_count' => count($postsToInsert),
                        'first_post' => $postsToInsert[0] ?? null,
                    ]);
                    throw $e;
                }

                $this->stats['posts_created'] += count($postsToInsert);
            }

            DB::commit();

            // Preserve inserted count for logging before unsetting variables
            $postsInserted = isset($postsToInsert) ? count($postsToInsert) : 0;

            // Force garbage collection after each chunk to prevent memory overflow
            unset($postsToInsert, $tagPivotData, $categoryPivotData, $chunk);

            // Clear any lingering references
            if (function_exists('gc_mem_caches')) {
                gc_mem_caches();
            }
            gc_collect_cycles();

            $chunkEndMemory = memory_get_usage(true);
            $chunkMemoryUsed = $chunkEndMemory - $chunkStartMemory;
            // Query count is only meaningful if logging is enabled externally (e.g., in tests)
            $queryCount = method_exists(DB::getFacadeRoot(), 'getQueryLog') ? count(DB::getQueryLog()) : 0;

            Log::channel('import')->info("Chunk {$chunkNumber} completed", [
                'posts_inserted' => $postsInserted,
                'successful' => $this->stats['successful'],
                'failed' => $this->stats['failed'],
                'memory_after' => $this->formatBytes($chunkEndMemory),
                'memory_used' => $this->formatBytes($chunkMemoryUsed),
                'query_count' => $queryCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('import')->error("Chunk {$chunkNumber} failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Continue with next chunk
            $this->stats['failed'] += count($postsToInsert ?? []);
        }
    }

    /**
     * Prepare post data from CSV row.
     */
    protected function preparePostData(array $row, array $options): ?array
    {
        // Validate required fields
        if (empty($row['title'])) {
            throw new \InvalidArgumentException('Title is required');
        }

        // Check for duplicates using a dedicated variable
        $postSlug = Str::slug($row['title']);
        if (DB::table('posts')->where('slug', $postSlug)->exists()) {
            Log::channel('import')->info('Duplicate post skipped', [
                'title' => $row['title'],
                'slug' => $postSlug,
            ]);

            return null;
        }

        // Parse tags and categories (accept 'tags' and 'keywords'), normalize to slug
        $tags = [];
        if (! empty($row['tags'])) {
            foreach ($this->parseCommaSeparated($row['tags']) as $t) {
                $tagSlug = Str::slug($t);
                if ($tagSlug) {
                    $tags[] = $tagSlug;
                }
            }
        }
        if (! empty($row['keywords'])) {
            foreach ($this->parseCommaSeparated($row['keywords']) as $t) {
                $tagSlug = Str::slug($t);
                if ($tagSlug) {
                    $tags[] = $tagSlug;
                }
            }
        }

        // Ensure unique tag slugs
        if (! empty($tags)) {
            $tags = array_values(array_unique($tags));
        }

        $categories = [];
        if (! empty($row['categories'])) {
            foreach ($this->parseCommaSeparated($row['categories']) as $c) {
                $catSlug = Str::slug($c);
                if ($catSlug) {
                    $categories[] = $catSlug;
                }
            }
        }

        // Get tag IDs
        $tagIds = [];
        foreach ($tags as $tagSlug) {
            if (isset($this->tagCache[$tagSlug])) {
                $tagIds[] = $this->tagCache[$tagSlug];
            }
        }

        // Ensure unique tag IDs
        if (! empty($tagIds)) {
            $tagIds = array_values(array_unique($tagIds));
        }

        // Get category IDs (use first as primary, attach the rest via pivot)
        $primaryCategoryId = null;
        $extraCategoryIds = [];
        if (! empty($categories)) {
            $resolved = [];
            foreach ($categories as $catSlug) {
                if (isset($this->categoryCache[$catSlug])) {
                    $resolved[] = $this->categoryCache[$catSlug];
                }
            }
            if (! empty($resolved)) {
                $primaryCategoryId = $resolved[0];
                $extraCategoryIds = array_values(array_unique(array_slice($resolved, 1)));
            }
        }

        // Skip posts without categories (category_id is required)
        if ($primaryCategoryId === null) {
            Log::channel('import')->warning('Post skipped - no valid categories', [
                'title' => $row['title'],
                'categories_from_csv' => $row['categories'] ?? null,
                'parsed_category_slugs' => $categories,
                'category_cache_size' => count($this->categoryCache),
                'sample_cache_keys' => array_slice(array_keys($this->categoryCache), 0, 5),
            ]);

            return null;
        }

        // Generate content if enabled
        $content = '';
        $excerpt = '';
        if (! ($options['skip_content'] ?? false) && config('import.content_generation.enabled', true)) {
            $content = $this->contentGenerator->generateContent($row['title'], $tags);
            $excerpt = Str::limit(strip_tags($content), 200);
            $this->stats['content_generated']++;
        }

        // Assign image if enabled
        $featuredImage = null;
        $imageAltText = null;
        if (! ($options['skip_images'] ?? false) && config('import.image_generation.enabled', true)) {
            $imageData = $this->imageGenerator->assignImage($row['title'], $tags);
            $featuredImage = $imageData['path'];
            $imageAltText = $imageData['alt'];
            $this->stats['images_assigned']++;
        }

        // Calculate reading time
        $readingTime = $content ? Post::calculateReadingTime($content) : 0;

        // Determine status and published_at
        $status = $options['status'] ?? config('import.default_status', 'published');
        $publishedAt = $status === 'published' ? now() : null;

        // Build post data
        $postData = [
            'user_id' => $options['user_id'] ?? config('import.default_user_id', 1),
            'category_id' => $primaryCategoryId,
            'title' => $row['title'],
            'slug' => $postSlug,
            'excerpt' => $excerpt,
            'content' => $content,
            'featured_image' => $featuredImage,
            'image_alt_text' => $imageAltText,
            'status' => $status,
            'is_featured' => false,
            'is_trending' => false,
            'view_count' => 0,
            'published_at' => $publishedAt,
            'reading_time' => $readingTime,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Store tag IDs separately for pivot table
        $postData['tag_ids'] = $tagIds;
        $postData['extra_category_ids'] = $extraCategoryIds;

        return $postData;
    }

    /**
     * Parse comma-separated values.
     */
    protected function parseCommaSeparated(string $value): array
    {
        return array_map('trim', explode(',', $value));
    }

    /**
     * Format bytes to human-readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Get import statistics.
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Perform post-import operations (cache invalidation, search index rebuild, etc.)
     */
    protected function performPostImportOperations(array $options): void
    {
        Log::channel('import')->info('Starting post-import operations');

        try {
            // Invalidate caches
            $this->invalidateCaches();

            // Rebuild search index if enabled
            if (config('import.post_import.rebuild_search_index', false)) {
                $this->rebuildSearchIndex();
            }

            Log::channel('import')->info('Post-import operations completed');
        } catch (\Exception $e) {
            Log::channel('import')->error('Post-import operations failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't throw - post-import operations are not critical
        }
    }

    /**
     * Invalidate all relevant caches after import.
     */
    protected function invalidateCaches(): void
    {
        Log::channel('import')->info('Invalidating caches');

        // Invalidate search index caches
        $this->cacheService->invalidateByPattern('search.*');

        // Invalidate post caches
        $this->cacheService->invalidateAllViews();
        $this->cacheService->invalidateAllQueries();

        // Invalidate homepage cache
        $this->cacheService->invalidateHomepage();

        // Trigger sitemap regeneration
        $this->sitemapService->regenerateIfNeeded();

        Log::channel('import')->info('Cache invalidation completed');
    }

    /**
     * Rebuild search index after import.
     */
    protected function rebuildSearchIndex(): void
    {
        Log::channel('import')->info('Rebuilding search index');

        try {
            \Illuminate\Support\Facades\Artisan::call('search:rebuild-index');
            Log::channel('import')->info('Search index rebuild completed');
        } catch (\Exception $e) {
            Log::channel('import')->warning('Search index rebuild failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
