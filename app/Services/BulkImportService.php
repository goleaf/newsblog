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

        Log::channel('import')->info('Import started', [
            'file' => $filePath,
            'options' => $options,
        ]);

        try {
            // Parse CSV file lazily
            $rows = $this->csvParser->parseLazy($filePath);

            // Count total rows for progress tracking
            $totalRows = 0;
            foreach ($rows as $row) {
                $totalRows++;
            }

            // Pre-fetch and cache tags and categories
            $rows = $this->csvParser->parseLazy($filePath);
            $this->initializeCaches($rows, $options);

            // Reset rows iterator after cache initialization
            $rows = $this->csvParser->parseLazy($filePath);

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
        $allTags = collect();
        $allCategories = collect();

        // Collect all unique tags and categories from CSV
        foreach ($rows as $row) {
            if (! empty($row['tags'])) {
                $tags = $this->parseCommaSeparated($row['tags']);
                $allTags = $allTags->merge($tags);
            }

            if (! empty($row['categories'])) {
                $categories = $this->parseCommaSeparated($row['categories']);
                $allCategories = $allCategories->merge($categories);
            }
        }

        $allTags = $allTags->unique()->filter();
        $allCategories = $allCategories->unique()->filter();

        // Fetch existing tags and categories
        $existingTags = Tag::whereIn('name', $allTags)->get()->keyBy('name');
        $existingCategories = Category::whereIn('name', $allCategories)->get()->keyBy('name');

        // Create missing tags
        $newTags = $allTags->diff($existingTags->keys());
        if ($newTags->isNotEmpty()) {
            $tagsToInsert = [];
            foreach ($newTags as $tagName) {
                $slug = Str::slug($tagName);
                // Check if slug already exists
                if (! Tag::where('slug', $slug)->exists()) {
                    $tagsToInsert[] = [
                        'name' => $tagName,
                        'slug' => $slug,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (! empty($tagsToInsert)) {
                DB::table('tags')->insert($tagsToInsert);
                $this->stats['tags_created'] = count($tagsToInsert);
            }

            // Refresh tag cache
            $existingTags = Tag::whereIn('name', $allTags)->get()->keyBy('name');
        }

        // Create missing categories
        $newCategories = $allCategories->diff($existingCategories->keys());
        if ($newCategories->isNotEmpty()) {
            $categoriesToInsert = [];
            foreach ($newCategories as $categoryName) {
                $slug = Str::slug($categoryName);
                // Check if slug already exists
                if (! Category::where('slug', $slug)->exists()) {
                    $categoriesToInsert[] = [
                        'name' => $categoryName,
                        'slug' => $slug,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (! empty($categoriesToInsert)) {
                DB::table('categories')->insert($categoriesToInsert);
                $this->stats['categories_created'] = count($categoriesToInsert);
            }

            // Refresh category cache
            $existingCategories = Category::whereIn('name', $allCategories)->get()->keyBy('name');
        }

        // Build lookup maps
        $this->tagCache = $existingTags->mapWithKeys(function ($tag) {
            return [$tag->name => $tag->id];
        })->toArray();

        $this->categoryCache = $existingCategories->mapWithKeys(function ($category) {
            return [$category->name => $category->id];
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

        // Enable query logging for this chunk
        DB::flushQueryLog();
        DB::enableQueryLog();

        Log::channel('import')->info("Processing chunk {$chunkNumber}", [
            'rows' => $chunk->count(),
            'memory_before' => $this->formatBytes($chunkStartMemory),
        ]);

        try {
            DB::beginTransaction();

            $postsToInsert = [];
            $pivotData = [];
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

                    // Extract tag IDs for later pivot insertion
                    $tagIds = $postData['tag_ids'] ?? [];
                    unset($postData['tag_ids']);

                    $postsToInsert[] = $postData;

                    // Store tag relationships for later (we'll get post IDs after insert)
                    if (! empty($tagIds)) {
                        $pivotData[] = [
                            'post_index' => count($postsToInsert) - 1,
                            'tag_ids' => $tagIds,
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
                Post::withoutEvents(function () use ($postsToInsert, $pivotData) {
                    DB::table('posts')->insert($postsToInsert);

                    // Get the IDs of inserted posts
                    $firstSlug = $postsToInsert[0]['slug'];
                    $lastSlug = $postsToInsert[count($postsToInsert) - 1]['slug'];

                    $insertedPosts = DB::table('posts')
                        ->whereBetween('created_at', [
                            now()->subSeconds(5),
                            now()->addSeconds(5),
                        ])
                        ->orderBy('id')
                        ->pluck('id', 'slug')
                        ->toArray();

                    // Build pivot table data
                    $pivotInserts = [];
                    foreach ($pivotData as $pivot) {
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
                });

                $this->stats['posts_created'] += count($postsToInsert);
            }

            DB::commit();

            // Force garbage collection after each chunk to prevent memory overflow
            unset($postsToInsert, $pivotData, $chunk);

            // Clear any lingering references
            if (function_exists('gc_mem_caches')) {
                gc_mem_caches();
            }
            gc_collect_cycles();

            $chunkEndMemory = memory_get_usage(true);
            $chunkMemoryUsed = $chunkEndMemory - $chunkStartMemory;
            $queryCount = count(DB::getQueryLog());

            Log::channel('import')->info("Chunk {$chunkNumber} completed", [
                'posts_inserted' => count($postsToInsert),
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

        // Check for duplicates
        $slug = Str::slug($row['title']);
        if (DB::table('posts')->where('slug', $slug)->exists()) {
            Log::channel('import')->info('Duplicate post skipped', [
                'title' => $row['title'],
                'slug' => $slug,
            ]);

            return null;
        }

        // Parse tags and categories
        $tags = ! empty($row['tags']) ? $this->parseCommaSeparated($row['tags']) : [];
        $categories = ! empty($row['categories']) ? $this->parseCommaSeparated($row['categories']) : [];

        // Get tag IDs
        $tagIds = [];
        foreach ($tags as $tagName) {
            if (isset($this->tagCache[$tagName])) {
                $tagIds[] = $this->tagCache[$tagName];
            }
        }

        // Get category ID (use first category)
        $categoryId = null;
        if (! empty($categories)) {
            $firstCategory = $categories[0];
            $categoryId = $this->categoryCache[$firstCategory] ?? null;
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
            'category_id' => $categoryId,
            'title' => $row['title'],
            'slug' => $slug,
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
