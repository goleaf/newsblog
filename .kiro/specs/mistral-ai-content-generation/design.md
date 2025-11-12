# Design Document

## Overview

This feature integrates the Mistral AI API into the Laravel application to automatically generate article content for posts that have titles but no content. The implementation uses the `php-mistral` package (https://github.com/partITech/php-mistral) to communicate with Mistral AI servers and generate markdown-formatted articles.

The solution consists of:
- Integration of the php-mistral composer package
- Configuration management for Mistral AI API credentials
- An Artisan command for batch content generation
- A service class to handle API communication and content processing
- Error handling and logging for failed generation attempts

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Artisan Command                          │
│              GeneratePostContentCommand                      │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                   Service Layer                              │
│              MistralContentService                           │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                  php-mistral Package                         │
│                  (External Library)                          │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                  Mistral AI API                              │
│                  (External Service)                          │
└─────────────────────────────────────────────────────────────┘
```

### Component Flow

1. **Command Execution**: Administrator runs `php artisan posts:generate-content`
2. **Query Posts**: Command queries database for posts with titles but null/empty content
3. **Service Processing**: For each post, the service constructs a prompt and calls Mistral AI
4. **API Communication**: php-mistral package handles HTTP communication with Mistral AI
5. **Content Storage**: Generated markdown content is saved to the post's content field
6. **Progress Reporting**: Command outputs progress and summary information

## Components and Interfaces

### 1. Configuration File

**File**: `config/mistral.php`

```php
return [
    'api_key' => env('MISTRAL_API_KEY'),
    'api_url' => env('MISTRAL_API_URL', 'https://api.mistral.ai'),
    'model' => env('MISTRAL_MODEL', 'mistral-medium'),
    'timeout' => env('MISTRAL_TIMEOUT', 30),
    'max_retries' => env('MISTRAL_MAX_RETRIES', 3),
    'retry_delay' => env('MISTRAL_RETRY_DELAY', 1000), // milliseconds
];
```

**Environment Variables** (to be added to `.env` and `.env.example`):
```
MISTRAL_API_KEY=your_api_key_here
MISTRAL_API_URL=https://api.mistral.ai
MISTRAL_MODEL=mistral-medium
MISTRAL_TIMEOUT=30
MISTRAL_MAX_RETRIES=3
MISTRAL_RETRY_DELAY=1000
```

### 2. Service Class

**File**: `app/Services/MistralContentService.php`

**Responsibilities**:
- Initialize php-mistral client with configuration
- Construct prompts for content generation based on post titles
- Handle API communication with retry logic
- Validate and format generated content
- Log errors and track generation metrics

**Key Methods**:
```php
public function __construct()
public function generateContent(string $title, ?string $category = null): string
protected function buildPrompt(string $title, ?string $category): string
protected function callMistralApi(string $prompt): string
protected function validateMarkdown(string $content): bool
protected function retryWithBackoff(callable $callback, int $maxRetries): mixed
```

### 3. Artisan Command

**File**: `app/Console/Commands/GeneratePostContentCommand.php`

**Command Signature**: `posts:generate-content`

**Options**:
- `--limit=N`: Limit the number of posts to process (default: no limit)
- `--dry-run`: Show which posts would be processed without generating content
- `--force`: Skip confirmation prompt

**Responsibilities**:
- Query posts without content
- Display progress information
- Call service for each post
- Update post records with generated content
- Output summary statistics

**Key Methods**:
```php
public function handle(): int
protected function getPostsWithoutContent(): Collection
protected function processPost(Post $post): bool
protected function displaySummary(int $total, int $success, int $failed): void
```

### 4. Database Query Scope

**Addition to**: `app/Models/Post.php`

```php
public function scopeWithoutContent($query)
{
    return $query->whereNotNull('title')
        ->where(function ($q) {
            $q->whereNull('content')
              ->orWhere('content', '');
        });
}
```

## Data Models

### Post Model Updates

The existing `Post` model already has the necessary fields:
- `title` (string): Source for content generation
- `content` (text): Target field for generated markdown
- `category_id` (integer): Optional context for generation
- `status` (string): Should remain unchanged during generation

**Query Pattern**:
```php
Post::withoutContent()
    ->with('category:id,name')
    ->select('id', 'title', 'content', 'category_id')
    ->get();
```

### Generated Content Structure

The generated content will be stored as markdown in the `content` field:

```markdown
# [Generated Title or Section]

[Introduction paragraph]

## Section 1

[Content...]

## Section 2

[Content...]

## Conclusion

[Closing paragraph]
```

## Error Handling

### Error Categories and Responses

1. **Configuration Errors**
   - Missing API key
   - Invalid configuration values
   - **Response**: Command fails with clear error message

2. **API Communication Errors**
   - Network timeout
   - API rate limiting
   - Invalid API response
   - **Response**: Log error, retry with exponential backoff (up to 3 times), skip post if all retries fail

3. **Content Validation Errors**
   - Empty response from API
   - Invalid markdown format
   - **Response**: Log warning, skip post, continue processing

4. **Database Errors**
   - Failed to update post
   - **Response**: Log error, continue processing remaining posts

### Logging Strategy

All errors will be logged using Laravel's logging system:

```php
Log::channel('mistral')->error('Failed to generate content', [
    'post_id' => $post->id,
    'title' => $post->title,
    'error' => $exception->getMessage(),
    'attempt' => $attemptNumber,
]);
```

**Log Channel Configuration** (to be added to `config/logging.php`):
```php
'mistral' => [
    'driver' => 'daily',
    'path' => storage_path('logs/mistral.log'),
    'level' => 'debug',
    'days' => 14,
],
```

## Testing Strategy

### Unit Tests

**File**: `tests/Unit/MistralContentServiceTest.php`

Test cases:
- `test_builds_prompt_correctly()`
- `test_validates_markdown_format()`
- `test_handles_api_timeout()`
- `test_retries_on_failure()`
- `test_throws_exception_on_missing_api_key()`

### Feature Tests

**File**: `tests/Feature/GeneratePostContentCommandTest.php`

Test cases:
- `test_command_generates_content_for_posts_without_content()`
- `test_command_skips_posts_with_existing_content()`
- `test_command_respects_limit_option()`
- `test_command_handles_dry_run_option()`
- `test_command_displays_progress_and_summary()`
- `test_command_continues_on_individual_post_failure()`
- `test_command_fails_gracefully_on_missing_api_key()`

### Integration Tests

**File**: `tests/Feature/MistralApiIntegrationTest.php`

Test cases (requires API key in test environment):
- `test_generates_real_content_from_mistral_api()`
- `test_handles_rate_limiting()`

**Note**: Integration tests should be marked with `@group integration` and skipped in CI unless API credentials are available.

### Mocking Strategy

For unit and feature tests, mock the Mistral AI API responses:

```php
// Mock successful response
Http::fake([
    'api.mistral.ai/*' => Http::response([
        'choices' => [
            ['message' => ['content' => '# Test Article\n\nGenerated content...']]
        ]
    ], 200)
]);

// Mock failure response
Http::fake([
    'api.mistral.ai/*' => Http::response([], 429) // Rate limit
]);
```

## Implementation Considerations

### Performance

- **Batch Processing**: Process posts sequentially to avoid rate limiting
- **Timeout Management**: Set reasonable timeout (30 seconds) to prevent hanging
- **Memory Efficiency**: Use chunking if processing large numbers of posts

### Security

- **API Key Protection**: Store API key in `.env`, never commit to version control
- **Input Sanitization**: Sanitize post titles before sending to API
- **Output Validation**: Validate generated content before saving to database

### Scalability

- **Queue Integration** (Future Enhancement): Consider moving to queued jobs for large batches
- **Rate Limiting**: Respect Mistral AI rate limits with built-in retry logic
- **Caching**: No caching needed as each generation is unique

### Monitoring

- **Success Metrics**: Track successful vs failed generations
- **Performance Metrics**: Log API response times
- **Error Tracking**: Comprehensive error logging for debugging

## Dependencies

### Composer Package

```json
{
    "require": {
        "partitech/php-mistral": "^1.0"
    }
}
```

### Installation Command

```bash
composer require partitech/php-mistral
```

## Configuration Steps

1. Install php-mistral package via composer
2. Create `config/mistral.php` configuration file
3. Add Mistral AI environment variables to `.env` and `.env.example`
4. Add mistral log channel to `config/logging.php`
5. Publish any required package assets (if applicable)

## Usage Examples

### Basic Usage

```bash
# Generate content for all posts without content
php artisan posts:generate-content

# Limit to 10 posts
php artisan posts:generate-content --limit=10

# Dry run to see what would be processed
php artisan posts:generate-content --dry-run

# Skip confirmation prompt
php artisan posts:generate-content --force
```

### Expected Output

```
Searching for posts without content...
Found 15 posts to process.

Processing: "Introduction to Laravel 12" [1/15]
✓ Content generated successfully

Processing: "Understanding PHP 8.4 Features" [2/15]
✓ Content generated successfully

...

Summary:
--------
Total posts processed: 15
Successful: 13
Failed: 2
Duration: 45 seconds
```

## Future Enhancements

1. **Queue Integration**: Move to background jobs for better scalability
2. **Content Templates**: Allow custom prompt templates per category
3. **Multi-language Support**: Generate content in different languages
4. **Content Review**: Add draft status for generated content requiring review
5. **Regeneration**: Add option to regenerate content for existing posts
6. **Batch Scheduling**: Schedule automatic content generation via cron
