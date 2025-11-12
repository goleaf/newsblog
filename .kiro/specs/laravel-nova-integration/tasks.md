# Implementation Plan

- [x] 1. Install and configure Laravel Nova
  - Copy Nova files from `.data/laravel-nova_v5.7.6` to vendor directory
  - Update `composer.json` with path repository configuration
  - Run composer update to register Nova package
  - Publish Nova configuration file with `php artisan nova:install`
  - Configure Nova settings in `config/nova.php` (path, name, middleware)
  - Publish Nova assets to public directory
  - Register Nova service provider in application
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 2. Create NovaServiceProvider and configure authentication
  - Generate `app/Providers/NovaServiceProvider.php`
  - Implement `Nova::auth()` gate to restrict access by user role
  - Register NovaServiceProvider in `bootstrap/providers.php`
  - Configure Nova to use existing User model and web guard
  - Set up authorization gate checking for admin, editor, author roles
  - _Requirements: 1.5, 7.1, 7.2, 7.3, 7.4_

- [x] 3. Create authorization policies for all models
  - Generate policy for Post model with viewAny, view, create, update, delete methods
  - Generate policy for User model with role-based permissions
  - Generate policy for Category model
  - Generate policy for Tag model
  - Generate policy for Comment model
  - Generate policy for Media model
  - Generate policy for Page model
  - Generate policy for Newsletter model
  - Generate policy for Setting model (admin-only)
  - Generate policy for ActivityLog model (read-only for admin/editor)
  - Register all policies in `AuthServiceProvider`
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [x] 4. Create Post Nova resource
  - Generate `app/Nova/Post.php` resource
  - Define fields: ID, Title, Slug, Excerpt, Content (Trix), Featured Image, Image Alt Text
  - Add relationship fields: BelongsTo User (author), BelongsTo Category, BelongsToMany Tags
  - Add status fields: Status select, Is Featured boolean, Is Trending boolean
  - Add date fields: Published At, Scheduled At (conditional on status)
  - Add readonly fields: Reading Time, View Count
  - Create SEO panel with Meta Title, Meta Description, Meta Keywords
  - Implement authorization methods using PostPolicy
  - Configure search fields (title, excerpt, content)
  - Implement indexQuery with eager loading for user, category, tags
  - _Requirements: 2.1, 2.2_

- [x] 5. Create User Nova resource
  - Generate `app/Nova/User.php` resource
  - Define fields: ID, Name, Email, Password (creation only)
  - Add role Select field with options: admin, editor, author, user
  - Add Avatar image field with public disk
  - Add Bio textarea field
  - Add Status select field: active, inactive, suspended
  - Add readonly fields: Email Verified At, Created At
  - Add HasMany relationship displays for posts count, comments count
  - Implement authorization methods using UserPolicy
  - Configure search fields (name, email)
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 6. Create Category Nova resource
  - Generate `app/Nova/Category.php` resource
  - Define fields: ID, Name, Slug (readonly), Description
  - Add Parent Category BelongsTo relationship (self-referencing)
  - Add Icon text field, Color Code color picker
  - Add Status select field, Display Order number field
  - Create SEO panel with Meta Title, Meta Description
  - Add HasMany relationship for child categories and posts
  - Implement authorization methods using CategoryPolicy
  - Configure ordering by display_order
  - _Requirements: 2.3, 2.4_

- [x] 7. Create Tag Nova resource
  - Generate `app/Nova/Tag.php` resource
  - Define fields: ID, Name, Slug (readonly)
  - Add BelongsToMany relationship display for posts
  - Show posts count in detail view
  - Implement authorization methods using TagPolicy
  - Configure search fields (name)
  - _Requirements: 2.5_

- [x] 8. Create Comment Nova resource
  - Generate `app/Nova/Comment.php` resource
  - Define fields: ID, Post (BelongsTo with link), User (BelongsTo, nullable)
  - Add Author Name and Author Email for guest comments
  - Add Content textarea field
  - Add Status select field: pending, approved, spam
  - Add readonly fields: IP Address, User Agent (hidden by default), Created At
  - Add BelongsTo Parent Comment and HasMany Replies relationships
  - Implement authorization methods using CommentPolicy
  - Configure search fields (content, author_name, author_email)
  - _Requirements: 2.6, 2.7_

- [x] 9. Create Media Nova resource
  - Generate `app/Nova/Media.php` resource
  - Define fields: ID, Thumbnail preview, File Name (readonly), File Path (readonly)
  - Add File Type badge field, File Size (human-readable), MIME Type
  - Add Alt Text, Title, Caption fields
  - Add BelongsTo User (uploaded by) relationship
  - Add readonly Created At field
  - Implement authorization methods using MediaPolicy
  - Configure search fields (file_name, title, alt_text)
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 10. Create Page Nova resource
  - Generate `app/Nova/Page.php` resource
  - Define fields: ID, Title, Slug (readonly), Content (Trix)
  - Add Template select field, Display Order number field
  - Add Status select field: draft, published
  - Create SEO panel with Meta Title, Meta Description
  - Add readonly fields: Created At, Updated At
  - Implement authorization methods using PagePolicy
  - Configure search fields (title, content)
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

- [x] 11. Create Newsletter Nova resource
  - Generate `app/Nova/Newsletter.php` resource
  - Define fields: ID, Email, Status select (active, unsubscribed)
  - Add readonly fields: Verified At, Created At
  - Add Token field (hidden by default)
  - Implement authorization methods using NewsletterPolicy
  - Configure search fields (email)
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

- [x] 12. Create Setting Nova resource
  - Generate `app/Nova/Setting.php` resource
  - Define fields: ID, Key (readonly), Value, Group select
  - Group settings by category: general, email, social, SEO
  - Implement authorization methods (admin-only access)
  - Add validation for setting values based on data types
  - Configure cache clearing on update
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

- [x] 13. Create ActivityLog Nova resource
  - Generate `app/Nova/ActivityLog.php` resource
  - Define fields: ID, Log Name, Description, Event
  - Add MorphTo relationships for Subject and Causer
  - Add Properties JSON field showing before/after values
  - Add readonly fields: IP Address, User Agent, Created At
  - Implement authorization methods (read-only for admin/editor)
  - Configure search fields (description, log_name)
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [x] 14. Create dashboard metrics
  - Create `app/Nova/Metrics/TotalPosts.php` value metric showing published posts count
  - Create `app/Nova/Metrics/TotalUsers.php` value metric showing active users count
  - Create `app/Nova/Metrics/TotalViews.php` value metric showing post views this month
  - Create `app/Nova/Metrics/PostsPerDay.php` trend metric with line chart
  - Create `app/Nova/Metrics/PostsByStatus.php` partition metric with donut chart
  - Create `app/Nova/Metrics/PostsByCategory.php` partition metric with bar chart
  - Register all metrics in Main dashboard
  - Implement caching for metric calculations (5-15 minutes)
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

- [x] 15. Create Main dashboard
  - Generate `app/Nova/Dashboards/Main.php` dashboard
  - Add all metrics from task 14
  - Configure dashboard as default
  - Set appropriate metric ranges and refresh intervals
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

- [x] 16. Create custom actions for posts
  - Create `app/Nova/Actions/PublishPosts.php` action to bulk publish draft posts
  - Create `app/Nova/Actions/FeaturePosts.php` action to toggle featured flag
  - Create `app/Nova/Actions/ExportPosts.php` action to export posts as CSV
  - Implement authorization checks (editor and admin only)
  - Add confirmation dialogs where appropriate
  - Add success/error messages
  - Register actions in Post resource
  - _Requirements: 6.1, 6.2, 6.3_

- [x] 17. Create custom actions for comments
  - Create `app/Nova/Actions/ApproveComments.php` action to bulk approve pending comments
  - Create `app/Nova/Actions/RejectComments.php` action to mark comments as spam
  - Implement authorization checks (editor and admin only)
  - Add success messages with count
  - Register actions in Comment resource
  - _Requirements: 6.4, 6.5_

- [x] 18. Create filters for Post resource
  - Create `app/Nova/Filters/PostStatus.php` filter for draft/published/scheduled
  - Create `app/Nova/Filters/PostCategory.php` filter for category selection
  - Create `app/Nova/Filters/PostAuthor.php` filter for author selection
  - Create `app/Nova/Filters/PostFeatured.php` filter for featured posts
  - Create `app/Nova/Filters/DateRange.php` filter for date range selection
  - Register all filters in Post resource
  - _Requirements: 8.3_

- [ ] 19. Create filters for other resources
  - Create `app/Nova/Filters/CommentStatus.php` filter for pending/approved/spam
  - Create `app/Nova/Filters/UserRole.php` filter for admin/editor/author/user
  - Create `app/Nova/Filters/CategoryStatus.php` filter for active/inactive
  - Create `app/Nova/Filters/MediaType.php` filter for image/document/video
  - Register filters in respective resources
  - _Requirements: 2.7, 3.4, 4.4, 8.4_

- [x] 20. Create Maintenance Mode tool
  - Generate `app/Nova/Tools/MaintenanceMode.php` tool
  - Create Vue component for maintenance mode controls
  - Add toggle switch for enable/disable maintenance mode
  - Add textarea for custom maintenance message
  - Add IP whitelist input field
  - Implement backend API endpoints for maintenance operations
  - Add authorization check (admin-only)
  - _Requirements: 14.1_

- [x] 21. Create Cache Manager tool
  - Generate `app/Nova/Tools/CacheManager.php` tool
  - Create Vue component for cache management interface
  - Add buttons for clearing application, route, config, view caches
  - Add "Clear All" button
  - Display last cleared timestamps
  - Implement backend API endpoints for cache operations
  - Add authorization check (admin-only)
  - _Requirements: 14.2_

- [x] 22. Create System Health tool
  - Generate `app/Nova/Tools/SystemHealth.php` tool
  - Create Vue component for system health dashboard
  - Display database connection status with badge
  - Display queue status and failed jobs count
  - Display storage usage (disk space)
  - Display recent errors from logs
  - Implement auto-refresh every 30 seconds
  - Add authorization check (admin-only)
  - _Requirements: 14.5_

- [x] 23. Update routes and middleware
  - Update admin routes to redirect to Nova equivalents
  - Add deprecation notices to old admin URLs
  - Update admin middleware to work with Nova authentication
  - Configure Nova path in `config/nova.php` to `/admin`
  - Test all route redirects
  - _Requirements: 13.1, 13.2, 13.5_

- [x] 24. Implement search functionality
  - Configure global search in NovaServiceProvider
  - Set up search fields for Post resource (title, excerpt, content)
  - Set up search fields for User resource (name, email)
  - Set up search fields for Category resource (name, description)
  - Set up search fields for Tag resource (name)
  - Set up search fields for Comment resource (content, author_name)
  - Set up search fields for Media resource (file_name, title, alt_text)
  - Test search functionality across all resources
  - _Requirements: 8.1, 8.2_

- [x] 25. Configure pagination and performance optimization
  - Set default pagination to 25 items per page for most resources
  - Set pagination to 50 items for Tag and Category resources
  - Set pagination to 10 items for Media resource
  - Implement eager loading in indexQuery for all resources with relationships
  - Implement selective field loading in relatableQuery methods
  - Add database indexes for commonly searched/filtered fields
  - _Requirements: 8.5_

- [🚧] 26. Implement activity logging for Nova actions (IN PROGRESS)
  - 🚧 Hook into Nova resource events (created, updated, deleted)
  - 🚧 Log all CRUD operations to ActivityLog model
  - 🚧 Capture user, IP address, user agent, and changes
  - ⏳ Implement automatic log archiving for logs older than 90 days
  - ⏳ Test logging for all resources
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [x] 27. Create feature tests for Nova resources
  - Write tests for Post resource CRUD operations
  - Write tests for User resource CRUD operations
  - Write tests for Category resource CRUD operations
  - Write tests for Comment resource CRUD operations
  - Write authorization tests for all resources (admin, editor, author, user roles)
  - Write tests for custom actions (publish, feature, approve)
  - Write tests for filters and search functionality
  - Run all tests to ensure they pass
  - _Requirements: All requirements_

- [x] 28. Remove deprecated admin panel code
  - Remove admin controllers from `app/Http/Controllers/Admin/`
  - Remove admin views from `resources/views/admin/`
  - Remove admin routes from routes file
  - Remove admin-specific middleware if no longer needed
  - Update any references to old admin URLs in codebase
  - Run tests to ensure nothing is broken
  - _Requirements: 13.4_

- [x] 29. Update documentation and create user guide
  - Document Nova installation process
  - Create user guide for admin users
  - Document custom actions and tools
  - Create troubleshooting guide
  - Update README with Nova information
  - Create video tutorials for common tasks
  - _Requirements: All requirements_

- [x] 30. Deploy and monitor Nova integration
  - Deploy Nova to staging environment
  - Perform user acceptance testing
  - Monitor performance metrics
  - Check error logs for issues
  - Deploy to production
  - Monitor production for 48 hours
  - Gather user feedback
  - _Requirements: All requirements_
