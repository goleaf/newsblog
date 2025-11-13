# Bulk News Importer Documentation

## Overview

The Bulk News Importer is a high-performance Laravel system designed to import hundreds of thousands of news articles from CSV files. The system is optimized for speed through bulk database operations, efficient memory management via chunk processing, and optional background job processing for large datasets.

## Features

- **High Performance**: Bulk database operations and optimized memory usage
- **Lazy Loading**: Memory-efficient CSV parsing using Laravel's LazyCollection
- **Automatic Content Generation**: Creates article content from titles and tags
- **Image Assignment**: Assigns featured images to imported articles
- **Tag & Category Management**: Automatically creates and associates tags and categories
- **Progress Tracking**: Real-time progress display with time estimates
- **Error Handling**: Graceful error handling with detailed logging
- **Background Processing**: Queue support for large imports
- **Duplicate Detection**: Skips duplicate articles based on title/slug

## Installation & Setup

### Prerequisites

- PHP 8.4+
- Laravel 12
- MySQL/PostgreSQL database
- Queue worker (for background processing)

### Configuration

The importer uses the `config/import.php` configuration file:

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
        'service' => env('IMPORT_IMAGE_SERVICE', 'unsplash'),
        'fallback_image' => 'images/default-post.jpg',
    ],
    
    'post_import' => [
        'rebuild_search_index' => env('IMPORT_REBUILD_SEARCH_INDEX', false),
    ],
];
```

### Environment Variables

Add these to your `.env` file:

```env
IMPORT_CHUNK_SIZE=1000
IMPORT_QUEUE_THRESHOLD=50000
IMPORT_DEFAULT_USER_ID=1
IMPORT_DEFAULT_STATUS=published
IMPORT_GENERATE_CONTENT=true
IMPORT_GENERATE_IMAGES=true
IMPORT_IMAGE_SERVICE=unsplash
IMPORT_REBUILD_SEARCH_INDEX=false
```

## CSV File Format

### Required Columns

The CSV file must contain these columns:

- `title` - Article title (required)
- `tags` - Comma-separated list of tags (required)
- `categories` - Comma-separated list of categories (required)

### Example CSV

```csv
title,tags,categories
"Getting Started with Laravel 12","laravel,php,web development","Backend,PHP"
"React Hooks Deep Dive","react,javascript,hooks","Frontend,JavaScript"
"Docker Best Practices","docker,devops,containers","DevOps,Tools"
```

### CSV Format Requirements

- UTF-8 or ASCII encoding
- Comma-separated values
- Double quotes for text containing commas
- Header row required
- Empty lines are skipped

## Command Usage

### Basic Import

Import a single CSV file:

```bash
php artisan news:import database/data/articles.csv
```

### Import Options

#### Chunk Size

Control the number of rows processed per batch (default: 1000):

```bash
php artisan news:import articles.csv --chunk-size=2000
```

#### Skip Content Generation

Import without generating article content (faster):

```bash
php artisan news:import articles.csv --skip-content
```

#### Skip Image Assignment

Import without assigning featured images:

```bash
php artisan news:import articles.csv --skip-images
```

#### Set Post Status

Import articles with a specific status:

```bash
# Import as drafts
php artisan news:import articles.csv --status=draft

# Import as published (default)
php artisan news:import articles.csv --status=published

# Import as scheduled
php artisan news:import articles.csv --status=scheduled
```

#### Assign to Specific User

Import articles and assign them to a specific user:

```bash
php artisan news:import articles.csv --user-id=5
```

#### Background Processing

Process large imports in the background:

```bash
php artisan news:import articles.csv --queue
```

Check import status:

```bash
php artisan news:import-status
```

### Directory Import

Import all CSV files in a directory:

```bash
php artisan news:import database/data/
```

This will process all `.csv` files in the directory sequentially and generate separate reports for each file.

### Combined Options

You can combine multiple options:

```bash
php artisan news:import articles.csv \
  --chunk-size=2000 \
  --skip-content \
  --status=draft \
  --user-id=5 \
  --queue
```

## Import Process

### 1. Validation

- Validates file/directory exists
- Validates CSV structure and required columns
- Validates command options

### 2. Cache Initialization

- Pre-fetches all existing tags and categories
- Creates missing tags and categories in bulk
- Builds in-memory lookup maps for fast access

### 3. Chunk Processing

- Processes CSV in configurable chunks (default: 1000 rows)
- Each chunk is wrapped in a database transaction
- Bulk inserts posts and relationships
- Garbage collection after each chunk

### 4. Post-Import Operations

- Invalidates relevant caches
- Optionally rebuilds search index
- Triggers sitemap regeneration

## Performance

### Benchmarks

Based on testing with 100,000 row CSV files:

| Configuration | Speed | Memory Peak | Notes |
|--------------|-------|-------------|-------|
| Full (content + images) | ~150-200 posts/sec | ~128 MB | Default settings |
| Skip content | ~300-400 posts/sec | ~64 MB | Faster, no content |
| Skip both | ~500-700 posts/sec | ~32 MB | Fastest, minimal data |

### Performance Tips

1. **Increase Chunk Size**: For powerful servers, increase chunk size to 2000-5000
2. **Skip Content Generation**: Use `--skip-content` for faster imports
3. **Skip Image Assignment**: Use `--skip-images` to save time
4. **Use Queue**: For imports over 50,000 rows, use `--queue`
5. **Disable Search Rebuild**: Set `IMPORT_REBUILD_SEARCH_INDEX=false`
6. **Optimize Database**: Ensure proper indexes exist on posts, tags, and categories tables

### Memory Management

The importer uses several techniques to prevent memory overflow:

- **Lazy Collections**: CSV files are read line-by-line, not loaded entirely into memory
- **Chunk Processing**: Data is processed in small batches
- **Garbage Collection**: Forced after each chunk to free memory
- **Bulk Operations**: Minimizes memory overhead from Eloquent models

## Error Handling

### Error Types

1. **File Errors**: Missing file, unreadable file, invalid format
2. **Validation Errors**: Missing required columns, invalid data types
3. **Database Errors**: Constraint violations, connection issues
4. **Generation Errors**: Content or image generation failures

### Error Recovery

- Errors are logged to `storage/logs/import-{date}.log`
- Failed chunks are rolled back, but import continues
- Duplicate articles are skipped and logged
- Summary report shows all errors at completion

### Viewing Logs

```bash
# View today's import log
tail -f storage/logs/import-$(date +%Y-%m-%d).log

# Search for errors
grep "ERROR" storage/logs/import-*.log

# View specific import session
grep "Import started" storage/logs/import-*.log
```

## Troubleshooting

### Common Issues

#### 1. Memory Limit Exceeded

**Symptom**: PHP fatal error about memory limit

**Solution**:
- Reduce chunk size: `--chunk-size=500`
- Increase PHP memory limit in `php.ini`: `memory_limit = 512M`
- Skip content generation: `--skip-content`

#### 2. Slow Import Speed

**Symptom**: Import taking too long

**Solution**:
- Increase chunk size: `--chunk-size=2000`
- Skip content/images: `--skip-content --skip-images`
- Use queue for background processing: `--queue`
- Check database indexes are present
- Disable search index rebuild

#### 3. Duplicate Key Errors

**Symptom**: Database errors about duplicate slugs

**Solution**:
- The importer automatically skips duplicates
- Check logs for which articles were skipped
- Clean up existing data if needed

#### 4. Missing Columns Error

**Symptom**: "Missing required columns" error

**Solution**:
- Ensure CSV has `title`, `tags`, and `categories` columns
- Check CSV header row is present
- Verify CSV encoding (UTF-8 or ASCII)

#### 5. Queue Job Not Processing

**Symptom**: Import queued but not running

**Solution**:
```bash
# Start queue worker
php artisan queue:work

# Check queue status
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

#### 6. High Database Query Count

**Symptom**: Too many queries per post

**Solution**:
- Check that tag/category caching is working
- Verify bulk inserts are being used
- Review logs for query count per chunk
- Expected: 3-5 queries per chunk (not per post)

### Debug Mode

Enable detailed logging by setting log level to debug in `config/logging.php`:

```php
'import' => [
    'driver' => 'daily',
    'path' => storage_path('logs/import.log'),
    'level' => 'debug', // Change from 'info' to 'debug'
    'days' => 14,
],
```

## Advanced Usage

### Custom Content Generation

Modify `app/Services/NewsContentGeneratorService.php` to customize content generation:

```php
public function generateContent(string $title, array $tags): string
{
    // Your custom content generation logic
    return $content;
}
```

### Custom Image Assignment

Modify `app/Services/NewsImageGeneratorService.php` to use different image sources:

```php
public function assignImage(string $title, array $tags): array
{
    // Your custom image assignment logic
    return [
        'path' => $imagePath,
        'alt' => $altText,
    ];
}
```

### Post-Import Hooks

Add custom logic after import in `app/Services/BulkImportService.php`:

```php
protected function performPostImportOperations(array $options): void
{
    parent::performPostImportOperations($options);
    
    // Your custom post-import logic
    $this->sendNotification();
    $this->updateStatistics();
}
```

## API Reference

### BulkImportService

Main service class for importing articles.

#### Methods

##### `import(string $filePath, array $options): array`

Imports articles from a CSV file.

**Parameters:**
- `$filePath` - Path to CSV file
- `$options` - Array of import options

**Returns:** Array of import statistics

**Example:**
```php
$result = $importService->import('articles.csv', [
    'chunk_size' => 1000,
    'skip_content' => false,
    'skip_images' => false,
    'user_id' => 1,
    'status' => 'published',
]);
```

##### `getStats(): array`

Returns current import statistics.

**Returns:** Array with keys:
- `total_rows` - Total rows processed
- `successful` - Successfully imported
- `failed` - Failed imports
- `skipped` - Skipped duplicates
- `posts_created` - Posts created
- `tags_created` - New tags created
- `categories_created` - New categories created
- `content_generated` - Content generated count
- `images_assigned` - Images assigned count
- `duration` - Import duration in seconds
- `memory_peak` - Peak memory usage
- `posts_per_second` - Import speed
- `total_queries` - Total database queries
- `queries_per_post` - Average queries per post

### CsvParserService

Service for parsing CSV files.

#### Methods

##### `parseLazy(string $filePath): LazyCollection`

Parses CSV file using lazy collection for memory efficiency.

##### `detectEncoding(string $filePath): string`

Detects file encoding (UTF-8 or ASCII).

##### `validateStructure(array $headers): bool`

Validates CSV has required columns.

## Best Practices

1. **Test with Small Files First**: Always test with a small CSV (100-1000 rows) before importing large datasets
2. **Use Queue for Large Imports**: For imports over 50,000 rows, use `--queue` option
3. **Monitor Memory Usage**: Check logs for memory usage per chunk
4. **Backup Database**: Always backup before large imports
5. **Validate CSV Format**: Ensure CSV is properly formatted before import
6. **Use Appropriate Chunk Size**: Balance between speed and memory usage
7. **Schedule Off-Peak**: Run large imports during off-peak hours
8. **Monitor Queue Workers**: Ensure queue workers are running for background imports

## Support

For issues or questions:

1. Check this documentation
2. Review logs in `storage/logs/import-*.log`
3. Check Laravel logs in `storage/logs/laravel.log`
4. Review the troubleshooting section above

## Changelog

### Version 1.0.0 (2025-11-13)

- Initial release
- Bulk import from CSV files
- Automatic content generation
- Image assignment
- Tag and category management
- Progress tracking
- Error handling and logging
- Background queue processing
- Performance monitoring
