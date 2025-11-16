# Implementation Plan

## Overview

This implementation plan breaks down the TechNewsHub platform into discrete, manageable coding tasks. Each task builds incrementally on previous work, ensuring a solid foundation before adding complexity. The plan follows an implementation-first approach where features are built before corresponding tests.

## Task Organization

Tasks are organized into phases:
1. **Foundation** - Core models, migrations, and basic structure
2. **Content Management** - Post creation, categories, tags, media
3. **User Features** - Authentication, comments, bookmarks
4. **Frontend** - Public-facing pages and components
5. **Advanced Features** - Search, notifications, widgets
6. **Performance & Polish** - Caching, optimization, accessibility

Optional tasks (marked with *) include testing and documentation that can be skipped for faster MVP delivery.

---

## Phase 1: Foundation & Core Models

- [ ] 1. Set up project structure and core configuration
  - Create fresh Laravel 12 project with SQLite database
  - Configure environment variables for development
  - Set up Tailwind CSS and Alpine.js with Vite
  - Configure Laravel Nova 4 for admin panel
  - _Requirements: 1, 10_

- [ ] 2. Create user authentication system with roles
- [ ] 2.1 Generate User model with role-based authorization
  - Create users migration with role enum (admin, editor, author, user)
  - Add status enum (active, suspended, inactive)
  - Implement role checking methods (isAdmin, isEditor, isAuthor)
  - _Requirements: 1, 17_

- [ ] 2.2 Implement authentication scaffolding with Laravel Breeze
  - Install and configure Laravel Breeze
  - Customize login/register views with Tailwind
  - Add role selection during registration (default: user)
  - _Requirements: 1_

- [ ]* 2.3 Write authentication tests
  - Test login/logout functionality
  - Test role-based access control
  - Test session management
  - _Requirements: 1_


- [ ] 3. Create core content models and migrations
- [ ] 3.1 Create Category model with hierarchical structure
  - Generate migration with parent_id for nested categories
  - Add slug, description, image, order, is_active fields
  - Implement parent/children relationships
  - Add soft deletes
  - _Requirements: 3_

- [ ] 3.2 Create Tag model
  - Generate migration with name, slug, description
  - Add timestamps
  - _Requirements: 3_

- [ ] 3.3 Create Post model with comprehensive fields
  - Generate migration with all post fields (title, slug, content, excerpt, etc.)
  - Add status enum (draft, scheduled, published, archived)
  - Add boolean flags (is_featured, is_breaking, is_sponsored, is_editors_pick)
  - Add SEO fields (meta_title, meta_description, meta_keywords)
  - Add soft deletes and timestamps
  - _Requirements: 2, 9, 14_

- [ ] 3.4 Create pivot tables for relationships
  - Create category_post pivot migration
  - Create post_tag pivot migration
  - Add appropriate indexes
  - _Requirements: 2, 3_

- [ ]* 3.5 Create model factories and seeders
  - Generate factories for User, Category, Tag, Post
  - Create seeder for sample data (10 categories, 50 tags, 100 posts)
  - _Requirements: 2, 3_

- [ ]* 3.6 Write model relationship tests
  - Test Category parent/child relationships
  - Test Post belongsToMany relationships
  - Test model scopes
  - _Requirements: 2, 3_


- [ ] 4. Implement Media Library system
- [ ] 4.1 Create Media model and migration
  - Generate migration with filename, path, mime_type, size fields
  - Add alt_text, caption, metadata (JSON) fields
  - Add user_id foreign key
  - _Requirements: 4, 18_

- [ ] 4.2 Create ImageProcessingService
  - Implement upload method with validation
  - Create generateVariants method (thumbnail, medium, large)
  - Implement optimize method for compression
  - Add convertToWebP method with JPEG fallback
  - Implement stripExif method
  - _Requirements: 4, 18_

- [ ] 4.3 Create MediaController for uploads
  - Implement store method with file validation
  - Add index method for media library listing
  - Add destroy method for file deletion
  - Implement search functionality
  - _Requirements: 4_

- [ ]* 4.4 Write media upload tests
  - Test file upload validation
  - Test image variant generation
  - Test file deletion
  - _Requirements: 4, 18_

## Phase 2: Content Management & Admin

- [ ] 5. Set up Laravel Nova admin panel
- [ ] 5.1 Create Nova resources for core models
  - Create PostResource with fields and filters
  - Create CategoryResource with parent selector
  - Create TagResource
  - Create UserResource with role management
  - Create MediaResource with preview
  - _Requirements: 2, 3, 4, 17_

- [ ] 5.2 Add Nova actions for post management
  - Create PublishPost action
  - Create SchedulePost action
  - Create ArchivePost action
  - Create BulkPublish action
  - _Requirements: 2, 14_

- [ ] 5.3 Create Nova dashboard with metrics
  - Add TotalPosts metric card
  - Add PostsPerDay trend metric
  - Add PendingComments value metric
  - Add PopularPosts table
  - _Requirements: 7_

- [ ]* 5.4 Customize Nova appearance
  - Configure branding (logo, colors)
  - Customize navigation menu
  - Add custom CSS for admin panel
  - _Requirements: 7_


- [ ] 6. Implement post management functionality
- [ ] 6.1 Create PostService for business logic
  - Implement create method with slug generation
  - Implement update method
  - Add publish method with timestamp
  - Add schedule method with validation
  - Implement calculateReadingTime method
  - _Requirements: 2, 14_

- [ ] 6.2 Create PostObserver for automatic actions
  - Implement creating event for slug generation
  - Add saving event for reading time calculation
  - Implement published event for notifications
  - Add deleted event for cleanup
  - _Requirements: 2_

- [ ] 6.3 Add post scopes to Post model
  - Create scopePublished for filtering published posts
  - Add scopeFeatured for featured posts
  - Create scopeBreaking for breaking news
  - Add scopeScheduled for scheduled posts
  - Implement scopePopular ordered by view_count
  - _Requirements: 2, 51_

- [ ]* 6.4 Write post management tests
  - Test post creation with relationships
  - Test slug generation and uniqueness
  - Test reading time calculation
  - Test post publishing workflow
  - _Requirements: 2_

- [ ] 7. Create comment system with moderation
- [ ] 7.1 Create Comment model and migration
  - Generate migration with post_id, user_id, parent_id
  - Add author_name, author_email for guests
  - Add content, status enum (pending, approved, spam, rejected)
  - Add ip_address, user_agent fields
  - _Requirements: 5, 23_

- [ ] 7.2 Implement SpamDetectionService
  - Create isSpam method checking multiple criteria
  - Implement checkLinkCount method (max 3 links)
  - Add checkBlacklist method for keywords
  - Create checkSubmissionSpeed method
  - Implement blockIp method with rate limiting
  - _Requirements: 5, 31_

- [ ] 7.3 Create CommentController for frontend
  - Implement store method with spam detection
  - Add reply method for nested comments
  - Create approve/reject methods for moderation
  - Add destroy method
  - _Requirements: 5, 23_

- [ ]* 7.4 Write comment system tests
  - Test comment submission
  - Test spam detection logic
  - Test nested replies (max 3 levels)
  - Test moderation workflow
  - _Requirements: 5, 23, 31_


## Phase 3: Frontend Public Pages

- [ ] 8. Create base layout and navigation components
- [ ] 8.1 Create main layout Blade template
  - Build header component with logo, navigation, search
  - Create footer component with links and newsletter form
  - Add sidebar component with widget areas
  - Implement mobile menu with Alpine.js
  - Add dark mode toggle with localStorage persistence
  - _Requirements: 10, 19_

- [ ] 8.2 Implement sticky navigation bar
  - Add scroll detection with Alpine.js
  - Create sticky header with reduced height
  - Add shadow effect when sticky
  - Implement smooth transitions
  - _Requirements: 75_

- [ ] 8.3 Create breaking news ticker component
  - Build horizontal scrolling ticker
  - Implement auto-rotation every 5 seconds
  - Add click handler to navigate to article
  - Filter posts older than 24 hours
  - _Requirements: 51_

- [ ] 8.4 Add scroll-to-top button
  - Show button after scrolling 300px
  - Implement smooth scroll to top
  - Add fade in/out animation
  - Position fixed in bottom-right corner
  - _Requirements: 74_

- [ ]* 8.5 Write layout component tests
  - Test mobile menu toggle
  - Test dark mode persistence
  - Test sticky navigation behavior
  - _Requirements: 10, 19, 75_

- [ ] 9. Build homepage with featured content
- [ ] 9.1 Create HomeController and index view
  - Implement hero section with featured post
  - Add breaking news section
  - Create category-based content sections
  - Add "Most Popular" sidebar widget
  - Implement "Trending Now" widget
  - _Requirements: 7, 51_

- [ ] 9.2 Add skeleton loading screens
  - Create skeleton components for post cards
  - Implement shimmer animation effect
  - Add fade-in transition when content loads
  - _Requirements: 72_

- [ ] 9.3 Implement lazy loading for images
  - Add loading="lazy" attribute to images
  - Create intersection observer for below-fold images
  - Implement blur-up placeholder technique
  - _Requirements: 12, 27_

- [ ]* 9.4 Write homepage tests
  - Test featured post display
  - Test breaking news ticker
  - Test lazy loading functionality
  - _Requirements: 7, 51, 72_


- [ ] 10. Create post display and article pages
- [ ] 10.1 Build PostController show method
  - Implement post retrieval with eager loading
  - Add view count tracking (prevent duplicates)
  - Load related posts using algorithm
  - Fetch approved comments with nesting
  - _Requirements: 2, 15, 22_

- [ ] 10.2 Create article page Blade template
  - Build article header with title, author, date
  - Add reading time and view count display
  - Implement social share buttons
  - Create bookmark button for logged-in users
  - Add font size controls
  - _Requirements: 2, 20, 38, 54_

- [ ] 10.3 Implement reading progress indicator
  - Add progress bar at top of page
  - Calculate progress based on scroll position
  - Update progress smoothly with CSS transitions
  - _Requirements: 21_

- [ ] 10.4 Create table of contents component
  - Auto-generate from H2/H3 headings (min 3)
  - Implement smooth scroll to sections
  - Add sticky positioning
  - Highlight active section on scroll
  - _Requirements: 58_

- [ ] 10.5 Add image zoom and lightbox
  - Implement click handler for article images
  - Create lightbox overlay with Alpine.js
  - Add navigation arrows for multiple images
  - Support keyboard navigation (Esc, arrows)
  - _Requirements: 55_

- [ ]* 10.6 Write article page tests
  - Test post display with relationships
  - Test view count increment
  - Test reading progress calculation
  - Test table of contents generation
  - _Requirements: 2, 15, 21, 58_

- [ ] 11. Implement category and tag pages
- [ ] 11.1 Create CategoryController show method
  - Fetch category with posts (including subcategories)
  - Implement pagination (15 posts per page)
  - Add breadcrumb navigation
  - Load category metadata for SEO
  - _Requirements: 3, 25_

- [ ] 11.2 Build category page template
  - Display category name and description
  - Show post grid with filters
  - Add sorting options (latest, popular)
  - Implement filter by date range
  - _Requirements: 3, 26_

- [ ] 11.3 Create TagController show method
  - Fetch tag with associated posts
  - Implement pagination
  - Add breadcrumb navigation
  - _Requirements: 3, 25_

- [ ] 11.4 Build tag page template
  - Display tag name and description
  - Show post grid with sorting
  - Add related tags section
  - _Requirements: 3_

- [ ]* 11.5 Write category/tag page tests
  - Test category hierarchy display
  - Test post filtering and sorting
  - Test breadcrumb generation
  - _Requirements: 3, 25, 26_


## Phase 4: Search & Discovery

- [ ] 12. Implement search functionality
- [ ] 12.1 Create SearchService with full-text search
  - Implement search method using SQLite FTS5
  - Add autocomplete method with debouncing
  - Create logSearch method for analytics
  - Implement getPopularSearches method
  - _Requirements: 8, 39_

- [ ] 12.2 Build SearchController
  - Implement index method with filters
  - Add autocomplete endpoint for AJAX
  - Create advanced search with date range, author, category filters
  - Implement result highlighting
  - _Requirements: 8, 39_

- [ ] 12.3 Create search page template
  - Build search form with filters
  - Display results with highlighting
  - Add pagination (15 results per page)
  - Show "no results" state with suggestions
  - Implement filter chips with clear all button
  - _Requirements: 8, 39_

- [ ] 12.4 Add voice search support
  - Implement Web Speech API integration
  - Add microphone button to search field
  - Create visual indicator for listening state
  - Handle speech-to-text conversion
  - Add fallback for unsupported browsers
  - _Requirements: 68_

- [ ]* 12.5 Write search functionality tests
  - Test full-text search accuracy
  - Test autocomplete suggestions
  - Test filter combinations
  - Test result ranking
  - _Requirements: 8, 39_

- [ ] 13. Create related posts algorithm
- [ ] 13.1 Implement RelatedPostsService
  - Create algorithm with category weight (40%)
  - Add tag matching weight (40%)
  - Implement date proximity weight (20%)
  - Add caching for 1 hour
  - Limit to 4 related posts
  - _Requirements: 22_

- [ ] 13.2 Add related posts section to article page
  - Display related posts with featured images
  - Show title and publication date
  - Add "Read more" links
  - _Requirements: 22_

- [ ]* 13.3 Write related posts algorithm tests
  - Test weight calculations
  - Test cache effectiveness
  - Test edge cases (no related posts)
  - _Requirements: 22_


## Phase 5: User Features & Engagement

- [ ] 14. Implement bookmark system
- [ ] 14.1 Create Bookmark model and migration
  - Generate migration with user_id, post_id
  - Add unique composite index
  - Add timestamps
  - _Requirements: 38_

- [ ] 14.2 Create BookmarkController
  - Implement store method (add bookmark)
  - Add destroy method (remove bookmark)
  - Create index method (user's reading list)
  - Add toggle endpoint for AJAX
  - _Requirements: 38_

- [ ] 14.3 Add bookmark button to post cards and articles
  - Create bookmark icon component (filled/outline)
  - Implement AJAX toggle without page reload
  - Add visual feedback on success
  - Show bookmark count (optional)
  - _Requirements: 38_

- [ ]* 14.4 Write bookmark system tests
  - Test bookmark creation
  - Test duplicate prevention
  - Test bookmark removal
  - Test reading list display
  - _Requirements: 38_

- [ ] 15. Create newsletter subscription system
- [ ] 15.1 Create Newsletter model and migration
  - Generate migration with email, status enum
  - Add verification_token, verified_at fields
  - Add unsubscribe_token field
  - Add unique index on email
  - _Requirements: 6_

- [ ] 15.2 Build NewsletterController
  - Implement subscribe method with double opt-in
  - Create verify method for email confirmation
  - Add unsubscribe method
  - Implement export method (CSV) for admins
  - _Requirements: 6_

- [ ] 15.3 Create newsletter subscription form component
  - Build form with email input
  - Add inline validation
  - Implement AJAX submission
  - Show success/error messages
  - _Requirements: 6_

- [ ] 15.4 Create email templates for newsletter
  - Design verification email template
  - Create confirmation email template
  - Build unsubscribe email template
  - _Requirements: 6, 24_

- [ ]* 15.5 Write newsletter tests
  - Test subscription flow
  - Test double opt-in verification
  - Test unsubscribe functionality
  - Test duplicate prevention
  - _Requirements: 6_


- [ ] 16. Implement notification system
- [ ] 16.1 Create Notification model and migration
  - Generate migration with user_id, type, data (JSON)
  - Add read_at timestamp
  - Add notifiable polymorphic relationship
  - _Requirements: 41_

- [ ] 16.2 Create NotificationService
  - Implement notifyCommentApproved method
  - Add notifyCommentReply method
  - Create notifyPostPublished method
  - Implement sendWelcomeEmail method
  - _Requirements: 24, 41_

- [ ] 16.3 Build notification UI components
  - Create notification bell icon with badge count
  - Build notification dropdown with list
  - Add "Mark all as read" button
  - Implement click to navigate and mark as read
  - _Requirements: 41_

- [ ] 16.4 Add notification cleanup job
  - Create scheduled job to delete old notifications (30+ days)
  - Schedule to run daily
  - _Requirements: 41_

- [ ]* 16.5 Write notification system tests
  - Test notification creation
  - Test notification display
  - Test mark as read functionality
  - Test cleanup job
  - _Requirements: 24, 41_

- [ ] 17. Create user dashboard and profile
- [ ] 17.1 Build DashboardController
  - Implement index method with user stats
  - Show bookmarked posts
  - Display reading history
  - Show notification summary
  - _Requirements: 38, 53_

- [ ] 17.2 Create dashboard page template
  - Build stats cards (bookmarks, comments, posts read)
  - Add recent bookmarks section
  - Show recent notifications
  - Display reading history
  - _Requirements: 38, 53_

- [ ] 17.3 Implement reading history tracking
  - Create PostView model and migration
  - Track views with session_id to prevent duplicates
  - Store IP address and user agent
  - Limit history to 100 most recent posts
  - _Requirements: 15, 53_

- [ ] 17.4 Add profile management
  - Create profile edit form
  - Implement avatar upload
  - Add bio field
  - Allow email preferences management
  - _Requirements: 17_

- [ ]* 17.5 Write dashboard tests
  - Test dashboard data display
  - Test reading history tracking
  - Test profile updates
  - _Requirements: 17, 38, 53_


## Phase 6: Advanced Features

- [ ] 18. Implement post series management
- [ ] 18.1 Create Series model and migration
  - Generate migration with name, slug, description
  - Add order field for post positioning
  - Add timestamps
  - _Requirements: 37_

- [ ] 18.2 Add series relationship to Post model
  - Add series_id foreign key to posts table
  - Add order_in_series field
  - Implement belongsTo relationship
  - _Requirements: 37_

- [ ] 18.3 Create series navigation component
  - Build previous/next post links
  - Add progress indicator (e.g., "Part 3 of 5")
  - Show series title and description
  - Display all posts in series
  - _Requirements: 37_

- [ ]* 18.4 Write series management tests
  - Test series creation
  - Test post ordering in series
  - Test navigation links
  - _Requirements: 37_

- [ ] 19. Add post revision history
- [ ] 19.1 Create PostRevision model and migration
  - Generate migration with post_id, user_id
  - Add title, content, metadata (JSON) fields
  - Add created_at timestamp
  - _Requirements: 36_

- [ ] 19.2 Implement PostRevisionService
  - Create saveRevision method on post update
  - Add getRevisions method with pagination
  - Implement compareRevisions method with diff
  - Create restoreRevision method
  - Limit to 25 revisions per post
  - _Requirements: 36_

- [ ] 19.3 Build revision history UI in Nova
  - Add revision history action to PostResource
  - Create revision comparison view
  - Implement restore functionality
  - _Requirements: 36_

- [ ]* 19.4 Write revision history tests
  - Test revision creation on update
  - Test revision limit enforcement
  - Test restore functionality
  - _Requirements: 36_

- [ ] 20. Implement widget system
- [ ] 20.1 Create Widget and WidgetArea models
  - Generate migrations for widgets and widget_areas
  - Add type, settings (JSON), order fields
  - Implement relationships
  - _Requirements: 30_

- [ ] 20.2 Create WidgetService
  - Implement renderWidget method for each type
  - Add getWidgetsForArea method
  - Create updateWidgetOrder method
  - _Requirements: 30_

- [ ] 20.3 Build built-in widgets
  - Create RecentPostsWidget
  - Build PopularPostsWidget
  - Implement CategoriesWidget
  - Add TagsCloudWidget
  - Create NewsletterWidget
  - Build SearchWidget
  - Add CustomHTMLWidget
  - _Requirements: 30_

- [ ] 20.4 Create widget management UI in Nova
  - Build widget manager interface
  - Implement drag-and-drop ordering
  - Add widget configuration forms
  - _Requirements: 30_

- [ ]* 20.5 Write widget system tests
  - Test widget rendering
  - Test widget ordering
  - Test widget configuration
  - _Requirements: 30_


- [ ] 21. Create settings management system
- [ ] 21.1 Create Setting model and migration
  - Generate migration with key (unique), value (JSON)
  - Add group field for categorization
  - Add timestamps
  - _Requirements: 28_

- [ ] 21.2 Implement SettingsService
  - Create get method with caching (24 hours)
  - Add set method with cache invalidation
  - Implement getGroup method
  - Add validation for setting types
  - _Requirements: 28_

- [ ] 21.3 Build settings management UI in Nova
  - Create settings resource with grouped fields
  - Add "Send Test Email" button for email settings
  - Implement cache clearing on save
  - Group settings: General, SEO, Social, Email, Comments, Media
  - _Requirements: 28_

- [ ] 21.4 Seed default settings
  - Create seeder for default configuration
  - Include site name, tagline, posts per page
  - Add default SEO settings
  - Set default email configuration
  - _Requirements: 28_

- [ ]* 21.5 Write settings management tests
  - Test setting retrieval with caching
  - Test setting updates with cache invalidation
  - Test grouped settings
  - _Requirements: 28_

- [ ] 22. Implement activity logging system
- [ ] 22.1 Create ActivityLog model and migration
  - Generate migration with user_id, subject polymorphic
  - Add description, properties (JSON) fields
  - Add ip_address, user_agent fields
  - Add timestamps
  - _Requirements: 32_

- [ ] 22.2 Create LogsActivity trait
  - Implement automatic logging on model events
  - Add logActivity method
  - Store old and new values for updates
  - _Requirements: 32_

- [ ] 22.3 Add activity logging to key models
  - Apply trait to Post, Category, Tag, User models
  - Log create, update, delete events
  - Log setting changes
  - _Requirements: 32_

- [ ] 22.4 Build activity log viewer in Nova
  - Create ActivityLog resource
  - Add filters (user, action, model, date range)
  - Display with user name, action, description
  - _Requirements: 32_

- [ ] 22.5 Create activity log cleanup job
  - Archive entries older than 90 days
  - Keep only 10,000 most recent entries
  - Schedule to run weekly
  - _Requirements: 32_

- [ ]* 22.6 Write activity logging tests
  - Test automatic logging on model events
  - Test log filtering
  - Test cleanup job
  - _Requirements: 32_


## Phase 7: SEO & Performance

- [ ] 23. Implement SEO optimization features
- [ ] 23.1 Create SEO meta tags component
  - Build component for Open Graph tags
  - Add Twitter Card meta tags
  - Implement Schema.org Article markup
  - Add canonical URL tags
  - _Requirements: 9, 20_

- [ ] 23.2 Implement SitemapService
  - Create generateSitemap method
  - Include posts, categories, pages, tags
  - Add lastmod, changefreq, priority elements
  - Implement sitemap splitting (50,000 URLs max)
  - _Requirements: 44_

- [ ] 23.3 Create sitemap generation command
  - Build artisan command to generate sitemap
  - Schedule to run after post publish
  - Store sitemap in public directory
  - _Requirements: 44_

- [ ] 23.4 Add robots.txt generation
  - Create dynamic robots.txt route
  - Allow all search engine crawlers
  - Include sitemap URL
  - _Requirements: 9_

- [ ]* 23.5 Write SEO tests
  - Test meta tag generation
  - Test sitemap generation
  - Test robots.txt content
  - _Requirements: 9, 20, 44_

- [ ] 24. Implement caching strategy
- [ ] 24.1 Create CacheService
  - Implement rememberPost method (1 hour)
  - Add rememberCategory method (15 minutes)
  - Create rememberSettings method (24 hours)
  - Implement cache invalidation methods
  - _Requirements: 12_

- [ ] 24.2 Add page caching middleware
  - Cache homepage for 10 minutes
  - Cache category pages for 15 minutes
  - Cache post pages for 1 hour
  - Exclude authenticated users
  - _Requirements: 12_

- [ ] 24.3 Implement query result caching
  - Cache popular posts (1 hour)
  - Cache recent posts (10 minutes)
  - Cache category tree (1 day)
  - Cache menu items (1 day)
  - _Requirements: 12_

- [ ] 24.4 Add cache warming command
  - Create artisan command to warm cache
  - Pre-cache homepage data
  - Pre-cache popular categories
  - Schedule to run after deployments
  - _Requirements: 12_

- [ ]* 24.5 Write caching tests
  - Test cache hit/miss scenarios
  - Test cache invalidation
  - Test cache warming
  - _Requirements: 12_


- [ ] 25. Optimize frontend performance
- [ ] 25.1 Implement critical CSS extraction
  - Create script to extract critical CSS
  - Inline critical CSS in layout
  - Defer non-critical CSS loading
  - _Requirements: 12_

- [ ] 25.2 Add asset optimization
  - Configure Vite for code splitting
  - Implement tree shaking
  - Add minification for production
  - Enable gzip compression
  - _Requirements: 12_

- [ ] 25.3 Optimize images
  - Implement responsive images with srcset
  - Add WebP format with JPEG fallback
  - Configure lazy loading for all images
  - Add blur-up placeholder technique
  - _Requirements: 12, 18_

- [ ] 25.4 Add service worker for PWA
  - Create service worker for offline support
  - Cache static assets
  - Implement offline page
  - Add web manifest file
  - _Requirements: 50_

- [ ]* 25.5 Run Lighthouse performance audit
  - Test homepage performance score (target: 90+)
  - Test article page performance
  - Optimize based on recommendations
  - _Requirements: 12_

## Phase 8: Security & Compliance

- [ ] 26. Implement security measures
- [ ] 26.1 Add rate limiting middleware
  - Implement login rate limiting (5 attempts/minute)
  - Add comment submission rate limiting (3/minute)
  - Create API rate limiting (60/minute public, 120/minute auth)
  - Use sliding window algorithm
  - _Requirements: 13, 45_

- [ ] 26.2 Configure security headers
  - Add X-Frame-Options header
  - Set X-Content-Type-Options header
  - Configure Content-Security-Policy
  - Add Referrer-Policy header
  - Set Permissions-Policy header
  - _Requirements: 13_

- [ ] 26.3 Implement CSRF protection
  - Ensure CSRF tokens on all forms
  - Add CSRF middleware to routes
  - Handle CSRF token refresh
  - _Requirements: 13_

- [ ] 26.4 Add input sanitization
  - Implement HTMLPurifier for user content
  - Escape all output with Blade {{ }}
  - Validate and sanitize file uploads
  - _Requirements: 13_

- [ ]* 26.5 Write security tests
  - Test rate limiting enforcement
  - Test CSRF protection
  - Test XSS prevention
  - Test file upload validation
  - _Requirements: 13, 45_


- [ ] 27. Implement two-factor authentication
- [ ] 27.1 Add 2FA fields to users table
  - Add two_factor_secret (encrypted)
  - Add two_factor_recovery_codes (encrypted)
  - Add two_factor_confirmed_at timestamp
  - _Requirements: 34_

- [ ] 27.2 Create TwoFactorAuthController
  - Implement enable method with QR code generation
  - Add verify method for code validation
  - Create disable method
  - Generate 10 backup codes
  - _Requirements: 34_

- [ ] 27.3 Build 2FA setup UI
  - Create QR code display page
  - Add verification code input
  - Show backup codes after setup
  - Implement "Remember device" option (30 days)
  - _Requirements: 34_

- [ ] 27.4 Add 2FA to login flow
  - Check if user has 2FA enabled
  - Prompt for verification code after password
  - Implement account lockout after 5 failed attempts (15 minutes)
  - _Requirements: 34_

- [ ]* 27.5 Write 2FA tests
  - Test 2FA setup flow
  - Test login with 2FA
  - Test backup codes
  - Test account lockout
  - _Requirements: 34_

- [ ] 28. Implement GDPR compliance features
- [ ] 28.1 Create cookie consent banner
  - Build banner component with accept/decline
  - Store consent in localStorage
  - Show on first visit only
  - _Requirements: 42_

- [ ] 28.2 Add data export functionality
  - Create export method in User model
  - Generate JSON with all user data
  - Include posts, comments, bookmarks
  - Provide download link
  - _Requirements: 42_

- [ ] 28.3 Implement account deletion
  - Create delete account method
  - Anonymize user data (keep posts)
  - Delete personal information
  - Send confirmation email
  - _Requirements: 42_

- [ ] 28.4 Create privacy policy page
  - Build static page template
  - Add customizable content
  - Include data collection disclosure
  - Add contact information
  - _Requirements: 42_

- [ ]* 28.5 Write GDPR compliance tests
  - Test cookie consent
  - Test data export
  - Test account deletion
  - _Requirements: 42_


## Phase 9: Additional Features

- [ ] 29. Implement maintenance mode
- [ ] 29.1 Create maintenance mode middleware
  - Check if maintenance mode is enabled
  - Allow admin access
  - Support secret token bypass
  - Allow IP whitelist
  - _Requirements: 46_

- [ ] 29.2 Build maintenance page
  - Create custom maintenance view
  - Display estimated return time
  - Add contact information
  - Return HTTP 503 status
  - _Requirements: 46_

- [ ] 29.3 Add maintenance mode commands
  - Create artisan command to enable maintenance
  - Add command to disable maintenance
  - Support secret token generation
  - _Requirements: 46_

- [ ]* 29.4 Write maintenance mode tests
  - Test maintenance page display
  - Test admin bypass
  - Test secret token access
  - _Requirements: 46_

- [ ] 30. Create broken link checker
- [ ] 30.1 Create BrokenLink model and migration
  - Generate migration with post_id, url, status
  - Add checked_at, response_code fields
  - Add timestamps
  - _Requirements: 47_

- [ ] 30.2 Implement CheckBrokenLinks job
  - Scan all published posts for external links
  - Check each link for HTTP status
  - Mark 404 and timeout as broken
  - Create report of broken links
  - Schedule to run weekly
  - _Requirements: 47_

- [ ] 30.3 Build broken links UI in Nova
  - Create BrokenLink resource
  - Display affected posts and URLs
  - Add "Fix" and "Ignore" actions
  - Show last checked timestamp
  - _Requirements: 47_

- [ ]* 30.4 Write broken link checker tests
  - Test link scanning
  - Test broken link detection
  - Test report generation
  - _Requirements: 47_

- [ ] 31. Implement alt text validation
- [ ] 31.1 Create AltTextValidator service
  - Scan post content for images
  - Check for missing alt attributes
  - Generate validation report
  - _Requirements: 48_

- [ ] 31.2 Add alt text validation to post save
  - Run validation before saving post
  - Display warning for missing alt text
  - Allow save with warnings
  - _Requirements: 48_

- [ ] 31.3 Create accessibility report in Nova
  - Build report showing posts with missing alt text
  - Add bulk edit interface
  - Show image count per post
  - _Requirements: 48_

- [ ]* 31.4 Write alt text validation tests
  - Test image scanning
  - Test alt text detection
  - Test report generation
  - _Requirements: 48_


- [ ] 32. Add content import/export
- [ ] 32.1 Create BulkImportService
  - Implement WordPress XML parser
  - Add category/tag mapping logic
  - Create post import with relationships
  - Handle media downloads
  - _Requirements: 35_

- [ ] 32.2 Build import UI in Nova
  - Create file upload interface
  - Add mapping configuration
  - Show import progress
  - Display import summary
  - _Requirements: 35_

- [ ] 32.3 Implement export functionality
  - Create JSON export method
  - Add CSV export for posts
  - Implement Markdown export with frontmatter
  - Include media files in ZIP
  - _Requirements: 35_

- [ ] 32.4 Add export UI in Nova
  - Create export configuration form
  - Add date range filter
  - Allow category/tag selection
  - Provide download link
  - _Requirements: 35_

- [ ]* 32.5 Write import/export tests
  - Test WordPress import
  - Test JSON export
  - Test data integrity
  - _Requirements: 35_

## Phase 10: Enhanced UI Features

- [ ] 33. Implement advanced UI components
- [ ] 33.1 Create photo gallery component
  - Build gallery with thumbnail navigation
  - Add full-screen mode
  - Implement auto-play slideshow (3s intervals)
  - Add image counter display
  - Support swipe gestures on touch devices
  - _Requirements: 56_

- [ ] 33.2 Add pull quotes styling
  - Create pull quote component
  - Implement float left/right with text wrap
  - Add quotation mark decorations
  - Support attribution text
  - Ensure mobile responsiveness
  - _Requirements: 57_

- [ ] 33.3 Implement embedded social media
  - Add Twitter/X embed support
  - Implement Facebook post embeds
  - Add Instagram post embeds
  - Lazy load embedded content
  - Show fallback links on error
  - _Requirements: 59_

- [ ] 33.4 Create interactive charts component
  - Integrate Chart.js library
  - Support line, bar, pie, area charts
  - Add hover tooltips
  - Allow CSV data input
  - Ensure mobile touch support
  - _Requirements: 60_

- [ ]* 33.5 Write UI component tests
  - Test gallery navigation
  - Test pull quote rendering
  - Test social media embeds
  - Test chart interactivity
  - _Requirements: 56, 57, 59, 60_


- [ ] 34. Add interactive widgets
- [ ] 34.1 Create polls widget
  - Build poll creation interface
  - Implement voting mechanism
  - Prevent duplicate votes (IP-based, 24 hours)
  - Display results with percentage bars
  - Add poll expiration
  - _Requirements: 61_

- [ ] 34.2 Implement weather widget
  - Integrate weather API
  - Detect user location via geolocation
  - Display temperature and conditions
  - Cache results for 30 minutes
  - Show default location if geolocation denied
  - _Requirements: 62_

- [ ] 34.3 Create stock market ticker
  - Integrate financial data API
  - Display real-time stock prices
  - Show price changes with color coding (green/red)
  - Update every 60 seconds
  - Add click-through to details
  - _Requirements: 63_

- [ ] 34.4 Build countdown timer widget
  - Create countdown component
  - Display days, hours, minutes, seconds
  - Update every second with JavaScript
  - Show completion message at zero
  - Allow customization of labels
  - _Requirements: 64_

- [ ]* 34.5 Write widget tests
  - Test poll voting
  - Test weather data fetching
  - Test stock ticker updates
  - Test countdown timer
  - _Requirements: 61, 62, 63, 64_

- [ ] 35. Implement additional UI enhancements
- [ ] 35.1 Add keyboard shortcuts
  - Implement "/" to focus search
  - Add "Esc" to close modals
  - Create "N/P" for next/previous page
  - Add "?" to show shortcuts help modal
  - _Requirements: 71_

- [ ] 35.2 Create parallax scrolling effects
  - Add parallax to homepage hero
  - Limit to desktop (1024px+)
  - Maintain 60fps performance
  - Respect reduced motion preferences
  - _Requirements: 73_

- [ ] 35.3 Implement print-friendly version
  - Create print stylesheet
  - Remove navigation, sidebar, ads
  - Include title, author, date, content
  - Optimize for black and white printing
  - _Requirements: 69_

- [ ] 35.4 Add QR code generation
  - Integrate QR code library
  - Create QR code button on articles
  - Display in modal with download option
  - Generate with error correction
  - _Requirements: 70_

- [ ]* 35.5 Write UI enhancement tests
  - Test keyboard shortcuts
  - Test parallax effects
  - Test print stylesheet
  - Test QR code generation
  - _Requirements: 69, 70, 71, 73_


## Phase 11: API & Integration

- [ ] 36. Build RESTful API
- [ ] 36.1 Create API controllers
  - Build PostApiController with CRUD operations
  - Create CategoryApiController
  - Add TagApiController
  - Implement CommentApiController
  - _Requirements: 11_

- [ ] 36.2 Implement API resources
  - Create PostResource for JSON transformation
  - Build CategoryResource
  - Add TagResource
  - Create CommentResource
  - Include pagination metadata
  - _Requirements: 11_

- [ ] 36.3 Add API authentication with Sanctum
  - Configure Laravel Sanctum
  - Create token generation endpoint
  - Implement token validation middleware
  - Add token revocation
  - _Requirements: 11_

- [ ] 36.4 Implement API rate limiting
  - Set public endpoints to 60 requests/minute
  - Set authenticated endpoints to 120 requests/minute
  - Return HTTP 429 with Retry-After header
  - Use sliding window algorithm
  - _Requirements: 11, 45_

- [ ] 36.5 Create API documentation
  - Build interactive API docs at /docs
  - Document all endpoints with examples
  - Include authentication instructions
  - Add rate limiting information
  - _Requirements: 11_

- [ ]* 36.6 Write API tests
  - Test all CRUD operations
  - Test authentication flow
  - Test rate limiting
  - Test error responses
  - Test pagination
  - _Requirements: 11, 45_

- [ ] 37. Implement live updates with WebSockets
- [ ] 37.1 Configure Laravel Echo and Pusher
  - Set up broadcasting configuration
  - Install and configure Pusher
  - Set up Laravel Echo on frontend
  - _Requirements: 52_

- [ ] 37.2 Create post published event
  - Build PostPublished event
  - Implement ShouldBroadcast interface
  - Add event broadcasting
  - _Requirements: 52_

- [ ] 37.3 Add live update notification UI
  - Create notification banner component
  - Listen for new post events
  - Display "View new post" button
  - Show count badge for multiple updates
  - _Requirements: 52_

- [ ]* 37.4 Write WebSocket tests
  - Test event broadcasting
  - Test notification display
  - Test reconnection logic
  - _Requirements: 52_


## Phase 12: Monitoring & Analytics

- [ ] 38. Implement performance monitoring
- [ ] 38.1 Create PerformanceMetricsService
  - Track page load times
  - Monitor database query times
  - Calculate cache hit/miss ratios
  - Log slow queries (>100ms)
  - Track memory usage
  - _Requirements: 43_

- [ ] 38.2 Build performance dashboard in Nova
  - Display average page load time (24 hours)
  - Show slow query log
  - Display cache statistics
  - Add memory usage chart
  - Create alerts for thresholds
  - _Requirements: 43_

- [ ] 38.3 Add performance monitoring middleware
  - Measure request duration
  - Log slow requests
  - Track database query count
  - Monitor memory usage per request
  - _Requirements: 43_

- [ ]* 38.4 Write performance monitoring tests
  - Test metrics collection
  - Test slow query detection
  - Test alert generation
  - _Requirements: 43_

- [ ] 39. Create analytics and reporting
- [ ] 39.1 Enhance view tracking
  - Track post views with session deduplication
  - Store IP address and user agent
  - Record referrer information
  - Track time spent on page
  - _Requirements: 15_

- [ ] 39.2 Build analytics dashboard
  - Display views over last 30 days (chart)
  - Show most popular categories
  - Display top performing posts
  - Add traffic sources breakdown
  - _Requirements: 15_

- [ ] 39.3 Create search analytics
  - Track search queries and result counts
  - Log search clicks
  - Display popular searches
  - Show zero-result queries
  - _Requirements: 8_

- [ ]* 39.4 Write analytics tests
  - Test view tracking
  - Test analytics calculations
  - Test search logging
  - _Requirements: 8, 15_

- [ ] 40. Implement backup system
- [ ] 40.1 Create database backup command
  - Build artisan command for backup
  - Copy SQLite database with timestamp
  - Store in backups directory
  - Upload to cloud storage (optional)
  - _Requirements: 33_

- [ ] 40.2 Add backup retention policy
  - Keep backups for 30 days
  - Delete older backups automatically
  - Schedule daily backups at 2:00 AM
  - _Requirements: 33_

- [ ] 40.3 Create restore command
  - Build artisan command to restore from backup
  - List available backups
  - Validate backup file before restore
  - Create backup before restore
  - _Requirements: 33_

- [ ]* 40.4 Write backup system tests
  - Test backup creation
  - Test retention policy
  - Test restore functionality
  - _Requirements: 33_


## Phase 13: Accessibility & Polish

- [ ] 41. Implement accessibility features
- [ ] 41.1 Add ARIA landmarks and labels
  - Add role attributes to layout sections
  - Implement aria-label for icon buttons
  - Add aria-describedby for form hints
  - Use aria-live for dynamic updates
  - _Requirements: 10_

- [ ] 41.2 Ensure keyboard navigation
  - Add visible focus indicators (2px outline)
  - Implement logical tab order
  - Add skip to main content link
  - Create focus trap in modals
  - _Requirements: 71_

- [ ] 41.3 Implement screen reader support
  - Use semantic HTML (article, section, nav)
  - Add proper heading hierarchy (h1-h6)
  - Use button for actions, a for navigation
  - Add labels to all form inputs
  - _Requirements: 10_

- [ ] 41.4 Ensure color contrast compliance
  - Test all text for WCAG AA contrast (4.5:1)
  - Test large text for 3:1 minimum
  - Test UI components for 3:1 contrast
  - Verify dark mode contrast ratios
  - _Requirements: 19_

- [ ]* 41.5 Run accessibility audit
  - Use axe DevTools for automated testing
  - Run Lighthouse accessibility audit
  - Test with screen reader (NVDA/JAWS)
  - Fix identified issues
  - _Requirements: 10, 19_

- [ ] 42. Add infinite scroll and pagination
- [ ] 42.1 Implement infinite scroll component
  - Detect scroll position (200px from bottom)
  - Load next page via AJAX
  - Append posts with fade-in animation
  - Update URL with pushState
  - _Requirements: 27_

- [ ] 42.2 Add loading states
  - Show loading spinner during fetch
  - Display skeleton screens
  - Show "End of content" message
  - Handle loading errors gracefully
  - _Requirements: 27, 72_

- [ ] 42.3 Implement traditional pagination fallback
  - Add pagination links for no-JS users
  - Ensure SEO-friendly URLs
  - Maintain current page state
  - _Requirements: 27_

- [ ]* 42.4 Write infinite scroll tests
  - Test scroll detection
  - Test AJAX loading
  - Test URL updates
  - Test end of content
  - _Requirements: 27_

- [ ] 43. Create content calendar
- [ ] 43.1 Build calendar view component
  - Create monthly calendar grid
  - Display posts on their dates
  - Color-code by status (published, scheduled, draft)
  - Add month navigation
  - _Requirements: 40_

- [ ] 43.2 Implement drag-and-drop scheduling
  - Allow dragging posts to different dates
  - Update scheduled_at or published_at
  - Show confirmation on drop
  - Validate date changes
  - _Requirements: 40_

- [ ] 43.3 Add calendar sidebar
  - Show posts for selected date
  - Display post details
  - Add quick edit options
  - _Requirements: 40_

- [ ]* 43.4 Write content calendar tests
  - Test calendar rendering
  - Test drag-and-drop
  - Test date updates
  - _Requirements: 40_


## Phase 14: Final Polish & Deployment

- [ ] 44. Implement remaining UI features
- [ ] 44.1 Create most commented widget
  - Query top 5 posts by comment count
  - Exclude posts older than 30 days
  - Cache for 1 hour
  - Display with comment count badge
  - Link to comments section
  - _Requirements: 65_

- [ ] 44.2 Build editor's picks section
  - Add is_editors_pick flag to posts
  - Create drag-and-drop ordering interface
  - Display up to 6 picks with images
  - Show "Editor's Pick" badge
  - Auto-remove unpublished posts
  - _Requirements: 66_

- [ ] 44.3 Add sponsored content labels
  - Display "Sponsored" label prominently
  - Use distinctive color
  - Show on post cards and articles
  - Add filter to exclude sponsored posts
  - _Requirements: 67_

- [ ]* 44.4 Write final UI tests
  - Test most commented widget
  - Test editor's picks
  - Test sponsored labels
  - _Requirements: 65, 66, 67_

- [ ] 45. Create static pages
- [ ] 45.1 Create Page model and migration
  - Generate migration with title, slug, content
  - Add template field (default, full-width, contact, about)
  - Add parent_id for hierarchy
  - Add display_order field
  - _Requirements: 16_

- [ ] 45.2 Build PageController
  - Implement show method
  - Add contact form handling
  - Store contact messages
  - Send notification emails
  - _Requirements: 16_

- [ ] 45.3 Create page templates
  - Build default page template
  - Create full-width template
  - Add contact page with form
  - Create about page template
  - _Requirements: 16_

- [ ] 45.4 Add page management in Nova
  - Create Page resource
  - Add template selector
  - Implement drag-and-drop ordering
  - Support parent-child relationships
  - _Requirements: 16_

- [ ]* 45.5 Write static pages tests
  - Test page display
  - Test contact form submission
  - Test page hierarchy
  - _Requirements: 16_

- [ ] 46. Implement menu builder
- [ ] 46.1 Create Menu and MenuItem models
  - Generate migrations for menus and menu_items
  - Add location field (header, footer, mobile)
  - Add type field (link, page, category, tag)
  - Add order, parent_id fields
  - _Requirements: 29_

- [ ] 46.2 Build menu management UI in Nova
  - Create menu builder interface
  - Implement drag-and-drop ordering
  - Add item type selector
  - Support nested menu items
  - Allow CSS class and target configuration
  - _Requirements: 29_

- [ ] 46.3 Create menu rendering component
  - Build menu component for each location
  - Support unlimited nesting
  - Add active state highlighting
  - Implement mobile menu toggle
  - _Requirements: 29_

- [ ]* 46.4 Write menu builder tests
  - Test menu creation
  - Test item ordering
  - Test nested menus
  - _Requirements: 29_


- [ ] 47. Add multi-language support (optional)
- [ ] 47.1 Set up Laravel localization
  - Create language files for UI strings
  - Add language switcher component
  - Store language preference in cookie
  - _Requirements: 49_

- [ ] 47.2 Implement RTL support
  - Add RTL CSS for Arabic/Hebrew
  - Detect language direction
  - Apply dir attribute to html tag
  - _Requirements: 49_

- [ ] 47.3 Add post translation associations
  - Add language field to posts table
  - Create translation relationships
  - Display language switcher on posts
  - _Requirements: 49_

- [ ]* 47.4 Write localization tests
  - Test language switching
  - Test RTL layout
  - Test translation associations
  - _Requirements: 49_

- [ ] 48. Final testing and optimization
- [ ] 48.1 Run comprehensive test suite
  - Execute all unit tests
  - Run all feature tests
  - Execute browser tests (Dusk)
  - Fix any failing tests
  - _Requirements: All_

- [ ] 48.2 Perform security audit
  - Test for XSS vulnerabilities
  - Verify CSRF protection
  - Test rate limiting
  - Check file upload security
  - Verify authentication flows
  - _Requirements: 13, 34, 45_

- [ ] 48.3 Optimize database queries
  - Run query analyzer
  - Add missing indexes
  - Optimize N+1 queries
  - Test with large datasets
  - _Requirements: 12_

- [ ] 48.4 Run performance benchmarks
  - Test homepage load time
  - Measure article page performance
  - Check API response times
  - Verify cache effectiveness
  - Run Lighthouse audits
  - _Requirements: 12_

- [ ] 48.5 Create deployment documentation
  - Document environment setup
  - List required dependencies
  - Provide deployment steps
  - Include troubleshooting guide
  - Add backup/restore procedures
  - _Requirements: All_

- [ ] 49. Prepare for production deployment
- [ ] 49.1 Configure production environment
  - Set up production .env file
  - Configure database connection
  - Set up Redis for cache and queue
  - Configure email service
  - Set up CDN (optional)
  - _Requirements: All_

- [ ] 49.2 Set up queue workers
  - Configure Laravel Horizon
  - Set up supervisor for queue workers
  - Configure failed job handling
  - Set up queue monitoring
  - _Requirements: All_

- [ ] 49.3 Configure scheduled tasks
  - Set up cron job for scheduler
  - Verify all scheduled commands
  - Test backup schedule
  - Test sitemap generation
  - _Requirements: 14, 33, 44_

- [ ] 49.4 Set up monitoring and logging
  - Configure error tracking (Sentry)
  - Set up application monitoring
  - Configure log aggregation
  - Set up uptime monitoring
  - Create alert rules
  - _Requirements: 43_

- [ ] 49.5 Perform final deployment
  - Deploy code to production server
  - Run database migrations
  - Build and deploy assets
  - Clear and warm cache
  - Restart queue workers
  - Run smoke tests
  - Monitor error logs
  - _Requirements: All_

---

## Summary

This implementation plan provides a comprehensive roadmap for building the TechNewsHub platform. The tasks are organized into 14 phases, progressing from foundation to deployment. Each task is discrete and builds incrementally on previous work.

**Key Statistics:**
- Total Tasks: 49 major tasks
- Sub-tasks: 200+ individual coding steps
- Optional Tasks: 50+ testing and documentation tasks
- Requirements Covered: All 75 requirements

**Estimated Timeline:**
- MVP (Phases 1-6): 8-10 weeks
- Full Feature Set (Phases 1-11): 16-20 weeks
- Production Ready (All Phases): 20-24 weeks

The plan follows an implementation-first approach where features are built before tests, allowing for faster MVP delivery while maintaining code quality through comprehensive testing when needed.
