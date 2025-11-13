# Design Document

## Overview

The Bulk News Importer is a high-performance Laravel system designed to import hundreds of thousands of news articles from CSV files. The architecture prioritizes speed through bulk database operations, efficient memory management via chunk processing, and optional background job processing for large datasets. The system integrates with existing Post, Tag, and Category models while adding content generation and image assignment capabilities.

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Artisan Command                          │
│                  ImportNewsArticles                          │
│  - Validates input                                           │
│  - Orchestrates import flow                                  │
│  - Displays progress                                         │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│                  BulkImportService                           │
│  - Coordinates all import operations                         │
│  - Manages transactions                                      │
│  - Generates reports                                         │
└────┬────────────────┬────────────────┬──────────────────────┘
     │                │                │
     ▼                ▼                ▼
┌─────────┐    ┌──────────┐    ┌─────────────┐
│  CSV    │    │ Content  │    │   Image     │
│ Parser  │    │Generator │    │  Generator  │
└─────────┘    └──────────┘    └─────────────┘
     │                │                │
     └────────────────┴────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                    Database Layer                            │
│  - Posts table                                               │
│  - Tags table                                                │
│  - Categories table                                          │
│  - post_tag pivot table                                      │
└─────────────────────────────────────────────────────────────┘
```

### Performance Strategy

1. **Bulk Inserts**: Use `DB::table()->insert()` for inserting multiple records in single queries
2. **Chunk Processing**: Process CSV in configurable chunks (default 1000 rows) to manage memory
3. **Lazy Collections**: Use Laravel's lazy collections for memory-efficient CSV reading
4. **Relationship Caching**: Cache tag and category lookups to avoid repeated queries
5. **Deferred Events**: Disable model events during bulk import, trigger cache invalidation once at end
6. **Queue Processing**: Dispatch large imports to background jobs

## Components and Interfaces

### 1. Artisan Command: ImportNewsArticles

**Location**: `app/Console/Commands/ImportNewsArticles.php`

**Signature**: `news:import {file} {--chunk-size=1000} {--skip-content} {--skip-images} {--user-id=} {--status=published} {--queue}`

**Responsibilities**:
- Validate file path and options
- Initialize BulkImportService
- Display progress bar
- Output summary report

**Key Methods**:
```php
public function handle(BulkImportService $importService): int
{
    // Validate inputs
    // Configure import options
    // Execute import with progress tracking
    // Display results
}
```

### 2. Service: BulkImportService

**Location**: `app/Services/BulkImportService.php`

**Responsibilities**:
- Orchestrate the entire import process
- Manage database transactions
- Coordinate with CSV parser, content generator, and image generator
- Generate import reports
- Handle error logging

**Key Methods**:
```php
public function import(string $filePath, array $options): ImportResult
{
    // Parse CSV file
    // Process in chunks
    // Bulk insert posts
    // Bulk insert relationships
    // Generate report
}

protected function processChunk(Collection $rows, array $options): ChunkResult
{
    // Prepare post data
    // Bulk insert posts
    // Handle tags and categories
    // Create relationships
}

protected function preparePostData(array $row, array $options): array
{
    // Map CSV columns to post fields
    // Generate slug
    // Set timestamps
    // Calculate reading time
}
```

### 3. Service: CsvParserService

**Location**: `app/Services/CsvParserService.php`

**Responsibilities**:
- Read CSV files efficiently using lazy collections
- Detect file encoding
- Parse headers and map columns
- Validate row structure

**Key Methods**:
```php
public function parseLazy(string $filePath): LazyCollection
{
    // Open file with lazy collection
    // Parse headers
    // Yield rows as associative arrays
}

public function detectEncoding(string $filePath): string
{
    // Detect UTF-8, ASCII, etc.
}

public function validateStructure(array $headers): bool
{
    // Ensure required columns exist
}
```

### 4. Service: NewsContentGeneratorService

**Location**: `app/Services/NewsContentGeneratorService.php`

**Responsibilities**:
- Generate article content from titles and tags
- Create realistic tech news content
- Calculate reading time
- Handle generation failures gracefully

**Key Methods**:
```php
public function generateContent(string $title, array $tags): string
{
    // Use template-based generation or AI service
    // Create 500-1500 word content
    // Format as HTML
}

public function generateBulk(array $articles): array
{
    // Batch generate content for multiple articles
    // Return array of [id => content]
}
```

### 5. Service: NewsImageGeneratorService

**Location**: `app/Services/NewsImageGeneratorService.php`

**Responsibilities**:
- Assign or generate featured images
- Use placeholder services (unsplash, picsum, etc.)
- Generate alt text
- Handle image failures with fallbacks

**Key Methods**:
```php
public function assignImage(string $title, array $tags): array
{
    // Return ['path' => '...', 'alt' => '...']
    // Use placeholder service or local assets
}

public function generateAltText(string $title): string
{
    // Create descriptive alt text
}
```

### 6. Job: ProcessBulkImportJob

**Location**: `app/Jobs/ProcessBulkImportJob.php`

**Responsibilities**:
- Handle background import processing
- Update progress in cache
- Send completion notifications

**Key Methods**:
```php
public function handle(BulkImportService $importService): void
{
    // Execute import
    // Update progress cache
    // Send notification on completion
}
```

## Data Models

### Database Schema Changes

**Migration**: `add_import_fields_to_posts_table`

No schema changes required - existing Post model has all necessary fields:
- `title` (required)
- `slug` (auto-generated)
- `content` (generated)
- `excerpt` (generated from content)
- `featured_image` (assigned)
- `image_alt_text` (generated)
- `user_id` (configurable)
- `category_id` (from CSV)
- `status` (configurable)
- `published_at` (set based on status)
- `reading_time` (calculated)

### Tag and Category Handling

**Strategy**: Use `firstOrCreate` with caching to minimize database queries

```php
// Cache structure
$tagCache = []; // ['tag-name' => tag_id]
$categoryCache = []; // ['category-name' => category_id]

// Bulk create missing tags/categories before processing posts
$allTags = collect($csvData)->pluck('tags')->flatten()->unique();
$existingTags = Tag::whereIn('name', $allTags)->pluck('id', 'name');
$newTags = $allTags->diff($existingTags->keys());
// Bulk insert new tags
```

### Pivot Table Optimization

**Strategy**: Collect all post-tag relationships and bulk insert

```php
$pivotData = [];
foreach ($posts as $post) {
    foreach ($post['tag_ids'] as $tagId) {
        $pivotData[] = [
            'post_id' => $post['id'],
            'tag_id' => $tagId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
DB::table('post_tag')->insert($pivotData);
```

## Error Handling

### Error Categories

1. **File Errors**: Missing file, unreadable file, invalid format
2. **Validation Errors**: Missing required columns, invalid data types
3. **Database Errors**: Constraint violations, connection issues
4. **Generation Errors**: Content or image generation failures

### Error Handling Strategy

```php
try {
    DB::beginTransaction();
    
    // Process chunk
    $results = $this->processChunk($chunk, $options);
    
    DB::commit();
    
} catch (ValidationException $e) {
    DB::rollBack();
    $this->logValidationErrors($e, $chunkNumber);
    // Continue with next chunk
    
} catch (DatabaseException $e) {
    DB::rollBack();
    $this->logDatabaseError($e, $chunkNumber);
    // Continue with next chunk
    
} catch (Exception $e) {
    DB::rollBack();
    $this->logGeneralError($e, $chunkNumber);
    // Continue with next chunk
}
```

### Logging

**Log Channel**: `import` (separate log file)

**Log Entries**:
- Import start/end with file info
- Chunk processing progress
- Validation errors with row numbers
- Database errors with context
- Generation failures
- Final summary statistics

## Testing Strategy

### Unit Tests

1. **CsvParserService**
   - Test lazy collection parsing
   - Test encoding detection
   - Test header validation
   - Test malformed CSV handling

2. **NewsContentGeneratorService**
   - Test content generation with various titles
   - Test content length validation
   - Test HTML formatting
   - Test error handling

3. **NewsImageGeneratorService**
   - Test image URL generation
   - Test alt text generation
   - Test fallback behavior

4. **BulkImportService**
   - Test chunk processing logic
   - Test tag/category caching
   - Test duplicate detection
   - Test transaction handling

### Feature Tests

1. **ImportNewsArticles Command**
   - Test successful import with small dataset
   - Test progress display
   - Test option handling (--skip-content, --skip-images, etc.)
   - Test error reporting
   - Test queue dispatching

2. **End-to-End Import**
   - Test importing actual CSV file
   - Verify posts created correctly
   - Verify tags and categories created
   - Verify relationships established
   - Verify content and images assigned

3. **Performance Tests**
   - Test import speed with 10k, 50k, 100k records
   - Measure memory usage
   - Verify no N+1 queries
   - Test queue job processing

### Test Data

Create test CSV files:
- `test_small.csv` (100 rows)
- `test_medium.csv` (10,000 rows)
- `test_large.csv` (100,000 rows)
- `test_malformed.csv` (invalid data)

## Performance Optimizations

### 1. Disable Model Events

```php
Post::withoutEvents(function () {
    // Bulk insert operations
});

// Manually trigger cache invalidation once at end
$this->invalidateSearchCaches();
```

### 2. Use Raw Queries for Bulk Inserts

```php
DB::table('posts')->insert($postData); // Faster than Eloquent
```

### 3. Batch Relationship Inserts

```php
// Instead of: $post->tags()->attach($tagIds)
DB::table('post_tag')->insert($pivotData); // Much faster
```

### 4. Memory Management

```php
// Use lazy collections
LazyCollection::make(function () use ($file) {
    while (($line = fgets($file)) !== false) {
        yield $line;
    }
});

// Unset large variables after use
unset($chunkData);
gc_collect_cycles();
```

### 5. Database Indexing

Ensure indexes exist on:
- `posts.slug` (unique)
- `posts.user_id`
- `posts.category_id`
- `posts.status`
- `posts.published_at`
- `tags.slug` (unique)
- `categories.slug` (unique)
- `post_tag.post_id`
- `post_tag.tag_id`

## Configuration

**Config File**: `config/import.php`

```php
return [
    'chunk_size' => env('IMPORT_CHUNK_SIZE', 1000),
    'queue_threshold' => env('IMPORT_QUEUE_THRESHOLD', 50000),
    'default_user_id' => env('IMPORT_DEFAULT_USER_ID', 1),
    'default_status' => env('IMPORT_DEFAULT_STATUS', 'published'),
    'content_generation' => [
        'enabled' => env('IMPORT_GENERATE_CONTENT', true),
        'min_words' => 500,
        'max_words' => 1500,
    ],
    'image_generation' => [
        'enabled' => env('IMPORT_GENERATE_IMAGES', true),
        'service' => env('IMPORT_IMAGE_SERVICE', 'unsplash'), // unsplash, picsum, local
        'fallback_image' => 'images/default-post.jpg',
    ],
    'csv' => [
        'delimiter' => ',',
        'enclosure' => '"',
        'escape' => '\\',
    ],
];
```

## Integration Points

### 1. Existing Post Model

The importer will use the existing Post model but bypass Eloquent for bulk inserts. After import, the Post model's boot methods and relationships will work normally.

### 2. Search Index

After import completes, trigger search index rebuild:
```php
Artisan::call('search:rebuild');
```

### 3. Cache Invalidation

Invalidate relevant caches after import:
```php
Cache::tags(['posts', 'categories', 'tags'])->flush();
```

### 4. Sitemap Regeneration

Trigger sitemap regeneration after import:
```php
app(SitemapService::class)->regenerate();
```

## Command Usage Examples

```bash
# Basic import
php artisan news:import database/data/programming_blog_titles_100k_expanded_categories.csv

# Import with custom chunk size
php artisan news:import database/data/file.csv --chunk-size=2000

# Import without content generation (faster)
php artisan news:import database/data/file.csv --skip-content

# Import as drafts
php artisan news:import database/data/file.csv --status=draft

# Import to specific user
php artisan news:import database/data/file.csv --user-id=5

# Import in background
php artisan news:import database/data/file.csv --queue

# Import all CSV files in directory
php artisan news:import database/data/

# Check import status (when queued)
php artisan news:import-status
```

## Monitoring and Reporting

### Progress Tracking

```php
$progressBar = $this->output->createProgressBar($totalRows);
$progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
```

### Import Report

```
Import Summary
==============
File: programming_blog_titles_100k_expanded_categories.csv
Total Rows: 100,000
Successful: 99,850
Failed: 150
Skipped (Duplicates): 0

Posts Created: 99,850
Tags Created: 1,245
Categories Created: 89
Content Generated: 99,850
Images Assigned: 99,850

Duration: 8m 32s
Average Speed: 195 posts/second
Memory Peak: 128 MB

Errors logged to: storage/logs/import-2025-11-13.log
```
