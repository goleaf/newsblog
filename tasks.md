# Phase 5: User Features & Engagement

- [x] 14. Implement bookmark system
- [x] 14.1 Create Bookmark model and migration
  - Implemented anonymous bookmarks with `reader_token` + `post_id`, timestamps, composite unique index.
- [x] 14.2 Create BookmarkController
  - Added `index`, `store`, `destroy`, `toggle` with dedicated Form Requests.
- [ ] 14.3 Add bookmark button to post cards and articles
  - Added `<x-bookmark-button>` component and JS AJAX toggle.
  - Wired into post cards and article page.
- [ ] 14.4 Write bookmark system tests
  - Created feature tests for create, duplicate prevention, removal, and reading list.
## 17. Create user dashboard and profile — Plan (Pending auth decision)

Blocking decision needed before implementation:
- Option A: Session-based personal dashboard (no users/auth)
- Option B: Minimal `User` model and auth exclusively for dashboard/profile

Priority after decision:
1) 17.3 Implement reading history tracking
   - Create `PostView` model and migration
   - Track by `session_id`, dedupe per post per session
   - Store IP and user agent
   - Keep most recent 100 views
   - Tests for tracking and trimming
2) 17.1 Build `DashboardController`
   - `index`: stats (bookmarks, comments, posts read)
   - Show recent bookmarks
   - Display reading history
   - Show notification summary
   - Tests: dashboard data display
3) 17.2 Create dashboard page template
   - Tailwind stats cards
   - Recent bookmarks section
   - Recent notifications
   - Reading history list
   - Components: keep minimal count but reusable
4) 17.4 Add profile management
   - If Option A: local preferences (avatar, bio, email prefs) stored by session or key
   - If Option B: profile edit with avatar upload, bio, email preferences on user
   - Validation via Form Requests; tests for updates
5) 17.5 Dashboard tests
   - Dashboard data display
   - Reading history tracking and limiting
   - Profile updates

Conventions & constraints to uphold:
- TailwindCSS; remove Bootstrap and any CDNs if present
- All JS/CSS via npm; no inline assets in blades
- One layout; maximize component reuse with minimal component count
- All strings translatable (JSON-based), use translation helpers in blades/controllers
- Each controller method uses a Form Request for validation + messages
- No Livewire, no Docker
- Tests are PHPUnit feature/unit; run minimally-scoped tests per change

# Phase 1: Foundation & Core Models

- [x] 1. Set up project structure and core configuration
  - Laravel 12 with SQLite is configured
  - Tailwind CSS and Alpine.js with Vite are configured
  - Laravel Nova (v5) admin panel is configured

- [x] 2. Create user authentication system with roles
- [x] 2.1 Generate User model with role-based authorization
  - Users table has role enum (admin, editor, author, user)
  - Status enum (active, suspended, inactive) present
  - Role checking methods (isAdmin, isEditor, isAuthor) implemented

- [x] 2.2 Implement authentication scaffolding with Laravel Breeze
  - Breeze installed and configured
  - Tailwind-based auth views present
  - Role defaults to user on registration

- [ ] 2.3 Write authentication tests
  - Test login/logout functionality
  - Test role-based access control
  - Test session management

---

- [x] 15. Create newsletter subscription system
  - [x] 15.1 Create Newsletter model and migration
    - Generated migration with email (unique), status (string for enum casting), verification_token, verification_token_expires_at, verified_at, unsubscribe_token fields
    - Added unique index on email
    - Added enum `App\Enums\NewsletterStatus` and cast on model
  - [x] 15.2 Build NewsletterController
    - Implemented subscribe method with double opt-in and resend flow
    - Implemented verify method for email confirmation with expiry handling
    - Implemented unsubscribe method with token
    - Implemented export method for admins (kept pending product decision to remove per no-export rule)
  - [x] 15.3 Create newsletter subscription form component
    - Email input with inline validation and AJAX submission
    - Success/error messages via Alpine, all strings localized
  - [x] 15.4 Create email templates for newsletter
    - Verification and confirmation templates exist; subjects localized
    - Unsubscribe flow template exists
  - [x] 15.5 Write newsletter tests
    - Tests cover subscription, double opt-in verification, unsubscribe, duplicate prevention, export

1. Reset database with fresh migrations and seed data.
2. Ensure a default import user exists for posts.
3. Run CSV import command for 1000 articles with categories and tags.
4. Verify that posts, categories, and tags are correctly created and linked.

## Tasks

- **21. Create settings management system**
  - [x] 21.1 Create Setting model and migration
    - Key unique, value JSON, group, timestamps
  - [x] 21.2 Implement SettingsService
    - get with 24h caching, set with invalidation, getGroup, type validation
  - [ ] 21.3 Build settings management UI in Nova
    - Settings resource with grouped filtering, actions: Send Test Email, Clear Cache
    - Clear cache on save
    - Groups: General, SEO, Social, Email, Comments, Media
  - [x] 21.4 Seed default settings
    - Site name, tagline, posts per page, SEO defaults, email config
  - [x] 21.5 Write settings management tests
    - Service tests for caching, invalidation, grouped settings

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

- **16. Implement notification system** ✓
  - **16.1 Create Notification model and migration** ✓
    - Notification model exists with user_id, type, data (JSON), read_at timestamp
    - Updated model to use casts() method (Laravel 12)
    - Migration includes proper indexes and foreign key constraints
  - **16.2 Create NotificationService** ✓
    - Implemented notifyCommentApproved method
    - Added notifyCommentReply method
    - Created notifyPostPublished method
    - Implemented sendWelcomeEmail method
    - All methods properly typed with return types
  - **16.3 Build notification UI components** ✓
    - Notification bell icon with badge count exists
    - Notification dropdown with list implemented
    - "Mark all as read" button functionality working
    - Click to navigate and mark as read implemented
    - Tailwind CSS styling applied
  - **16.4 Add notification cleanup job** ✓
    - Created CleanupOldNotifications job
    - Job deletes old read notifications (30+ days)
    - Scheduled to run daily at 03:00
    - Integrated with NotificationService
  - **16.5 Write notification system tests** ✓
    - Test notification creation for all types
    - Test notification display and dropdown
    - Test mark as read functionality (single and all)
    - Test cleanup job deletes old notifications correctly
    - Test cleanup job keeps unread notifications
    - All 18 tests passing with 59 assertions

- **17.5 Write dashboard tests**
  - **17.5.1 Test dashboard data display** ✓
    - Test admin/editor dashboard displays metrics correctly
    - Test regular user dashboard displays stats correctly
    - Test dashboard shows recent bookmarks, comments, and reactions
    - Test dashboard displays search statistics for admins
  - **17.5.2 Test reading history tracking** ✓
    - Test PostView records are created when posts are viewed
    - Test reading history is tracked per user and session
    - Test duplicate views are prevented within same session
    - Test reading history displays correctly on dashboard
  - **17.5.3 Test profile updates** ✓
    - Test profile information can be updated
    - Test avatar upload and deletion
    - Test email preferences can be updated
    - Test validation rules for profile updates
