# Implementation Plan

- [ ] 1. Create configuration and database preparation
  - Create `config/import.php` configuration file with chunk size, queue threshold, content/image generation settings
  - Verify database indexes exist on posts, tags, categories, and pivot tables for optimal performance
  - Create import log channel in `config/logging.php`
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 2. Implement CSV Parser Service
  - [ ] 2.1 Create `CsvParserService` class with lazy collection parsing
    - Implement `parseLazy()` method using `LazyCollection` to read CSV files line-by-line
    - Implement `detectEncoding()` method to handle UTF-8 and ASCII files
    - Implement `validateStructure()` method to ensure required columns (title, tags, categories) exist
    - _Requirements: 6.1, 6.2, 6.3_

  - [ ] 2.2 Write unit tests for CSV parser
    - Test lazy collection parsing with various CSV formats
    - Test encoding detection
    - Test header validation and malformed CSV handling
    - _Requirements: 6.1, 6.2, 6.3_

- [ ] 3. Implement Content Generator Service
  - [ ] 3.1 Create `NewsContentGeneratorService` class
    - Implement `generateContent()` method that creates 500-1500 word HTML content from title and tags
    - Use template-based approach with tech news patterns and vocabulary
    - Implement `generateBulk()` method for batch content generation
    - Add error handling with graceful fallbacks
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

  - [ ]* 3.2 Write unit tests for content generator
    - Test content generation with various titles and tags
    - Test content length validation (500-1500 words)
    - Test HTML formatting and error handling
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [ ] 4. Implement Image Generator Service
  - [ ] 4.1 Create `NewsImageGeneratorService` class
    - Implement `assignImage()` method using placeholder services (Unsplash, Picsum)
    - Implement `generateAltText()` method to create descriptive alt text from titles
    - Add fallback to default image when generation fails
    - Support configuration for different image services
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [ ]* 4.2 Write unit tests for image generator
    - Test image URL generation
    - Test alt text generation
    - Test fallback behavior
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 5. Implement Bulk Import Service
  - [ ] 5.1 Create `BulkImportService` class with core import logic
    - Implement `import()` method that orchestrates the entire import process
    - Implement `processChunk()` method for chunk-based processing with configurable batch sizes
    - Implement `preparePostData()` method to map CSV columns to post fields
    - Add slug generation and duplicate detection based on title/slug
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 5.4_

  - [ ] 5.2 Implement tag and category handling with caching
    - Pre-fetch existing tags and categories into memory cache
    - Bulk create missing tags and categories before processing posts
    - Generate URL-friendly slugs for tags and categories
    - Build tag/category ID lookup maps for fast access
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

  - [ ] 5.3 Implement bulk database operations
    - Use `DB::table('posts')->insert()` for bulk post insertion
    - Collect post-tag relationships and bulk insert into pivot table
    - Wrap operations in database transactions per chunk
    - Disable Post model events during bulk operations
    - _Requirements: 1.4, 4.5, 5.1_

  - [ ] 5.4 Implement error handling and logging
    - Add try-catch blocks for validation, database, and generation errors
    - Log errors with row numbers and context to import log channel
    - Continue processing on chunk failures
    - Generate detailed import summary report
    - _Requirements: 5.1, 5.2, 5.3, 5.5, 2.5_

  - [ ] 5.5 Write unit tests for bulk import service
    - Test chunk processing logic
    - Test tag/category caching
    - Test duplicate detection
    - Test transaction handling and error recovery
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 4.1, 4.2, 5.1, 5.2, 5.3, 5.4_

- [ ] 6. Implement Artisan Command
  - [ ] 6.1 Create `ImportNewsArticles` command
    - Define command signature with file argument and options (--chunk-size, --skip-content, --skip-images, --user-id, --status, --queue)
    - Implement input validation for file path and options
    - Integrate with `BulkImportService` to execute import
    - Add progress bar display with percentage, time estimates, and memory usage
    - Display formatted summary report on completion
    - _Requirements: 1.1, 1.5, 8.1, 8.2, 8.3, 8.4, 8.5_

  - [ ] 6.2 Add support for directory processing
    - Detect if input path is a directory
    - Process all CSV files in directory sequentially
    - Generate separate reports for each file
    - _Requirements: 6.4, 6.5_

  - [ ]* 6.3 Write feature tests for import command
    - Test successful import with small test CSV (100 rows)
    - Test option handling (--skip-content, --skip-images, --status, --user-id)
    - Test progress display and error reporting
    - Test directory processing
    - _Requirements: 1.1, 1.5, 6.4, 6.5, 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 7. Implement background job processing
  - [ ] 7.1 Create `ProcessBulkImportJob` queue job
    - Implement job to handle background import processing
    - Store progress updates in cache for status checking
    - Send notification to administrator on completion
    - Handle job failures with retry logic
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

  - [ ] 7.2 Create `ImportStatusCommand` for checking progress
    - Implement command to display current import progress from cache
    - Show percentage complete, estimated time remaining, and current status
    - _Requirements: 7.5_

  - [ ]* 7.3 Write tests for queue job processing
    - Test job dispatching when threshold exceeded
    - Test progress cache updates
    - Test completion notifications
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 8. Implement post-import operations
  - [ ] 8.1 Add cache invalidation after import
    - Invalidate search index caches
    - Invalidate post, category, and tag caches
    - Trigger sitemap regeneration
    - _Requirements: 1.4, 2.5_

  - [ ] 8.2 Add search index rebuild trigger
    - Call `search:rebuild` command after successful import
    - Make it optional via configuration
    - _Requirements: 1.4_

- [ ] 9. Create test data and end-to-end testing
  - [ ] 9.1 Create test CSV files
    - Create `test_small.csv` with 100 rows
    - Create `test_medium.csv` with 10,000 rows
    - Create `test_malformed.csv` with invalid data
    - _Requirements: 5.5_

  - [ ]* 9.2 Write end-to-end integration tests
    - Test importing actual CSV file from database/data directory
    - Verify posts, tags, categories created correctly
    - Verify relationships established in pivot table
    - Verify content and images assigned
    - Measure import speed and memory usage
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 3.1, 3.2, 4.1, 4.2, 4.5_

- [ ] 10. Performance optimization and final integration
  - [ ] 10.1 Optimize memory usage
    - Implement garbage collection after chunk processing
    - Verify lazy collections prevent memory overflow
    - Test with 100k+ row CSV file
    - _Requirements: 1.3_

  - [ ] 10.2 Add performance monitoring
    - Log import speed (posts per second)
    - Log memory peak usage
    - Log database query count per chunk
    - _Requirements: 1.5_

  - [ ] 10.3 Create documentation
    - Document command usage with examples
    - Document configuration options
    - Document troubleshooting common issues
    - Add performance benchmarks to documentation
    - _Requirements: 1.1, 6.1, 6.2, 6.3, 8.1, 8.2, 8.3, 8.4, 8.5_
