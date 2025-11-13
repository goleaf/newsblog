# Implementation Plan

- [x] 1. Install and configure php-mistral package
  - Install partitech/php-mistral via composer
  - Create config/mistral.php configuration file with API settings
  - Add MISTRAL_* environment variables to .env.example
  - Add mistral log channel configuration to config/logging.php
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 2. Create MistralContentService
  - [x] 2.1 Implement service class structure and constructor
    - Create app/Services/MistralContentService.php
    - Initialize php-mistral client with configuration
    - Add dependency injection for configuration
    - _Requirements: 1.2, 3.1, 3.2_

  - [x] 2.2 Implement content generation method
    - Write generateContent() method that accepts title and optional category
    - Implement buildPrompt() method to construct AI prompts from post data
    - Implement callMistralApi() method with API communication logic
    - _Requirements: 3.1, 3.2, 3.3_

  - [x] 2.3 Add retry logic with exponential backoff
    - Implement retryWithBackoff() method for handling API failures
    - Configure retry attempts and delay from config
    - Add error logging for failed attempts
    - _Requirements: 3.4, 3.5_

  - [x] 2.4 Implement content validation
    - Write validateMarkdown() method to verify content format
    - Add basic markdown structure validation
    - _Requirements: 4.1_

- [x] 3. Add database query scope to Post model
  - Add scopeWithoutContent() method to app/Models/Post.php
  - Query for posts with non-null titles and null/empty content
  - _Requirements: 2.2, 2.3_

- [x] 4. Create GeneratePostContentCommand
  - [x] 4.1 Implement command structure and signature
    - Create app/Console/Commands/GeneratePostContentCommand.php using artisan make:command
    - Define command signature as posts:generate-content
    - Add command options: --limit, --dry-run, --force
    - Write command description
    - _Requirements: 2.1_

  - [x] 4.2 Implement post querying and processing logic
    - Write getPostsWithoutContent() method using the new scope
    - Implement processPost() method that calls MistralContentService
    - Add progress output for each post being processed
    - _Requirements: 2.2, 2.3, 5.1, 5.2_

  - [x] 4.3 Implement database update logic
    - Update post content field with generated markdown
    - Handle database update failures gracefully
    - Continue processing remaining posts on individual failures
    - _Requirements: 4.2, 4.3, 4.4, 5.4_

  - [x] 4.4 Add summary output and error handling
    - Implement displaySummary() method with statistics
    - Output total processed, success count, and failure count
    - Add error logging for failed generations
    - _Requirements: 5.3, 4.4_

- [x] 5. Write tests for MistralContentService
  - [x] 5.1 Create unit tests
    - Create tests/Unit/MistralContentServiceTest.php
    - Test prompt building logic
    - Test markdown validation
    - Test retry logic with mocked failures
    - Test exception handling for missing API key
    - _Requirements: 1.3, 3.4, 4.1_

- [x] 6. Write tests for GeneratePostContentCommand
  - [x] 6.1 Create feature tests
    - Create tests/Feature/GeneratePostContentCommandTest.php
    - Test command generates content for posts without content
    - Test command skips posts with existing content
    - Test --limit option functionality
    - Test --dry-run option functionality
    - Test progress output and summary display
    - Test error handling and continuation on failures
    - Mock Mistral AI API responses using Http::fake()
    - _Requirements: 2.2, 2.4, 4.2, 4.3, 4.4, 5.1, 5.2, 5.3, 5.4_

- [x] 7. Update documentation
  - [x] 7.1 Add usage instructions to README
    - Document command usage and options
    - Add configuration instructions
    - Include example .env variables
    - _Requirements: All_
