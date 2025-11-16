1. Reset database with fresh migrations and seed data.
2. Ensure a default import user exists for posts.
3. Run CSV import command for 1000 articles with categories and tags.
4. Verify that posts, categories, and tags are correctly created and linked.

## Tasks

- **3.5 Create model factories and seeders**
  - Ensure factories exist for `User`, `Category`, `Tag`, and `Post`.
  - Create a seeder that uses these factories to generate 10 categories, 50 tags, and 100 posts with realistic relationships.

- **3.6 Write model relationship tests**
  - Add tests for `Category` parent / child relationships and scopes.
  - Add tests for `Post` `belongsToMany` relationships with `Category` and `Tag`.
  - Add tests for key `Post` model scopes (published, featured, trending, scheduled, filters).

- **5. Set up Laravel Nova admin panel**
  - **5.1 Create Nova resources for core models**
    - Create PostResource with fields and filters
    - Create CategoryResource with parent selector
    - Create TagResource
    - Create UserResource with role management
    - Create MediaResource with preview
  - **5.2 Add Nova actions for post management**
    - Create PublishPost action
    - Create SchedulePost action
    - Create ArchivePost action
    - Create BulkPublish action
  - **5.3 Create Nova dashboard with metrics**
    - Add TotalPosts metric card
    - Add PostsPerDay trend metric
    - Add PendingComments value metric
    - Add PopularPosts table
  - **5.4 Customize Nova appearance**
    - Configure branding (logo, colors)
    - Customize navigation menu
    - Add custom CSS for admin panel

- **6. Implement post management functionality**
  - **6.1 Create PostService for business logic**
    - Implement create method with slug generation
    - Implement update method
    - Add publish method with timestamp
    - Add schedule method with validation
    - Implement calculateReadingTime method
  - **6.2 Create PostObserver for automatic actions**
    - Implement creating event for slug generation
    - Add saving event for reading time calculation
    - Implement published event for notifications
    - Add deleted event for cleanup
  - **6.3 Add post scopes to Post model**
    - Create scopePublished for filtering published posts
    - Add scopeFeatured for featured posts
    - Create scopeBreaking for breaking news
    - Add scopeScheduled for scheduled posts
    - Implement scopePopular ordered by view_count
  - **6.4 Write post management tests**
    - Test post creation with relationships
    - Test slug generation and uniqueness
    - Test reading time calculation
    - Test post publishing workflow

- **7. Comment system**
  - **7.1 Create Comment model and migration** ✓
    - Generated Comment Eloquent model with factory and database migration including post_id, user_id, parent_id, author fields, content, status enum, ip_address, and user_agent.
    - Added comprehensive unit tests for Comment model attributes, enum casting, and relationships.
  - **7.2 Implement `SpamDetectionService`** ✓
    - Implemented `isSpam` to orchestrate multiple checks (link count, submission speed, blacklist, honeypot, IP throttling).
    - Added `checkLinkCount`, `checkBlacklist`, and `checkSubmissionSpeed` helper methods.
    - Implemented `blockIp` using Laravel's rate limiting to throttle repeated spam submissions by IP.
  - **7.3 Create CommentController for frontend** ✓
    - Enhanced `store` method with spam detection and IP blocking.
    - Added `reply` method for nested comments with max nesting level validation.
    - Created `approve` and `reject` methods for moderation (admin/editor only).
    - Added `destroy` method with proper authorization (users can delete own comments, admins/editors can delete any).
    - Created request validation classes: ReplyCommentRequest, ApproveCommentRequest, RejectCommentRequest, DestroyCommentRequest.
    - Added comprehensive feature tests for all controller methods.
  - **7.4 Write comment system tests** ✓
    - Created comprehensive `CommentSystemTest` with 31 test cases covering all aspects of the comment system.
    - Tested comment submission: guest submissions, validation, IP/user agent storage, replies.
    - Tested spam detection: excessive links, blacklisted keywords, quick submissions, honeypot detection.
    - Tested nested replies: 3-level nesting limit, depth calculation, relationship loading, notification dispatching.
    - Tested moderation workflow: approve/reject by admin/editor, authorization checks, complete workflow from submission to deletion.
    - All 31 tests passing with 95 assertions.
