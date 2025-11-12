# Changelog

All notable changes to TechNewsHub will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Fuzzy search integration specification and requirements
- Search analytics with query logging and click tracking
- Spam detection service with multiple validation strategies
- Image processing service with automatic optimization
- Post scheduling system with automated publishing
- Comprehensive test coverage for core services

### Changed
- Reorganized project documentation into structured folders
- Enhanced task tracking with detailed phase breakdowns
- Improved database schema documentation with optimization recommendations

### Fixed
- Comment submission spam detection now properly validates all criteria
- Rate limiting correctly enforces submission limits

## [0.2.0] - 2025-11-12

### Added

#### Search & Analytics System
- **Search Logging**: Complete search query tracking with performance metrics
  - Query text, result count, and execution time tracking
  - Search type categorization (posts, tags, categories, admin)
  - Fuzzy search enablement tracking
  - Filter and threshold logging
  - User and IP tracking for analytics
- **Search Click Tracking**: Monitor which search results users click
  - Position tracking for click-through rate analysis
  - Relationship between search queries and clicked posts
  - Analytics for result relevance optimization
- **Database Tables**: `search_logs` and `search_clicks` with proper indexes
- **Factories**: SearchLogFactory and SearchClickFactory for testing
- **Seeders**: SearchLogSeeder for development data

#### Spam Detection System
- **SpamDetectionService**: Comprehensive spam detection with multiple strategies
  - Link count validation (max 3 links)
  - Submission speed checking (minimum 3 seconds)
  - Blacklisted keyword detection
  - Honeypot field validation
  - Configurable thresholds and keywords
- **Test Coverage**: Complete test suite for spam detection scenarios
  - Legitimate comment handling
  - Excessive link detection
  - Blacklisted keyword detection
  - Quick submission detection
  - Honeypot field detection
  - Rate limiting validation

#### Image Processing
- **ImageProcessingService**: Automated image optimization
  - Multiple size variant generation (thumbnail, medium, large)
  - Image compression with quality control
  - WebP format generation with fallback
  - EXIF metadata stripping
- **Test Coverage**: Comprehensive image processing tests

#### Post Management
- **PostService**: Business logic for post operations
  - Automatic slug generation with uniqueness
  - Reading time calculation (200 words/minute)
  - Post scheduling with status management
- **Scheduled Publishing**: Automated post publication
  - PublishScheduledPostsCommand for cron execution
  - Email notifications on publication
  - SendPostPublishedNotification job
- **Test Coverage**: Full test suite for post operations

#### Database Enhancements
- **Media Metadata**: JSON metadata column for media files
  - Image dimensions, format, and EXIF data storage
- **Scheduled Posts Index**: Composite index for efficient scheduled post queries
  - Optimized `(status, scheduled_at)` lookup

### Changed
- Enhanced comment system with spam detection integration
- Improved media library with metadata support
- Optimized database queries with strategic indexes

### Documentation
- Complete database schema documentation with ERD
- Relationship mapping and optimization recommendations
- Common query patterns and performance benchmarks
- Security considerations and backup strategies

## [0.1.0] - 2025-11-11

### Added

#### Core Content Management System
- **User Management**
  - Role-based access control (Admin, Editor, Author)
  - User profiles with avatar and bio
  - Account status management
  - Authentication with Laravel Breeze
- **Post System**
  - Full CRUD operations for blog posts
  - Rich text content with excerpts
  - Featured images with alt text
  - Post status workflow (draft, published, scheduled, archived)
  - Featured and trending post flags
  - View count tracking
  - SEO metadata (title, description, keywords)
  - Soft deletes for data recovery
- **Category System**
  - Hierarchical category structure
  - Category icons and color coding
  - SEO optimization per category
  - Display order management
- **Tag System**
  - Simple tagging for posts
  - Many-to-many relationship with posts
  - URL-friendly slugs

#### Engagement Features
- **Comment System**
  - Nested comments with threading (3 levels deep)
  - Comment moderation workflow
  - Guest and authenticated commenting
  - IP and user agent tracking
  - Soft deletes
- **Bookmarks**
  - User bookmark/save functionality
  - Reading list management
- **Reactions**
  - Multiple reaction types (like, love, laugh, wow, sad, angry)
  - Guest and authenticated reactions
  - IP tracking for guest reactions
- **Post Views**
  - Individual view tracking
  - IP and user agent logging
  - Analytics data collection

#### Content Organization
- **Media Library**
  - Centralized file management
  - Multiple file type support
  - Alt text and captions
  - File metadata storage
- **Post Revisions**
  - Version control for posts
  - Revision notes
  - Metadata tracking
- **Static Pages**
  - Custom page templates
  - SEO optimization
  - Display order management

#### System Features
- **Newsletter Management**
  - Email subscription system
  - Verification workflow
  - Unsubscribe functionality
- **Contact Messages**
  - Contact form submissions
  - Status tracking (new, read, replied)
- **Settings System**
  - Key-value configuration storage
  - Grouped settings
- **Activity Logging**
  - Polymorphic activity tracking
  - User action logging
  - IP and user agent tracking

#### API
- **RESTful API**
  - Post resource endpoints
  - API authentication with Sanctum
  - Rate limiting
  - PostResource for consistent responses
- **API Documentation**
  - Scribe integration
  - Interactive documentation at /docs

#### Frontend
- **Responsive Design**
  - Tailwind CSS v3 integration
  - Mobile-first approach
  - Alpine.js for interactivity
- **User Interface**
  - Homepage with featured posts
  - Post detail pages
  - Category and tag pages
  - Search functionality
  - User dashboard
  - Profile management

#### Admin Panel
- **Dashboard**
  - Key metrics and statistics
  - Recent activity overview
- **Content Management**
  - Post management interface
  - Category management
  - Tag management
  - Comment moderation
  - Media library
  - Page management
- **User Management**
  - User list and editing
  - Role assignment
  - Activity logs
- **System Management**
  - Settings configuration
  - Newsletter subscribers
  - Contact messages
  - Maintenance mode

#### Development Tools
- **Testing**
  - PHPUnit test suite
  - Feature tests for core functionality
  - Model factories for all entities
- **Database**
  - Comprehensive migrations
  - Seeders for development data
  - Factory definitions
- **Code Quality**
  - Laravel Pint for code formatting
  - EditorConfig for consistency

### Technical Stack
- **Backend**: Laravel 12, PHP 8.4
- **Database**: SQLite (development), MySQL/PostgreSQL ready
- **Frontend**: Blade templates, Alpine.js v3, Tailwind CSS v3
- **Authentication**: Laravel Breeze, Sanctum
- **Queue**: Database driver
- **Cache**: File driver (configurable)
- **Mail**: SMTP configuration

### Database Schema
- 18 tables with proper relationships
- Foreign key constraints
- Strategic indexes for performance
- Soft deletes on critical tables
- JSON columns for flexible data

### Security
- CSRF protection
- XSS prevention
- SQL injection protection via Eloquent
- Rate limiting on API and forms
- Password hashing with bcrypt
- Email verification
- Secure session management

---

## Version History Summary

- **v0.2.0** - Search analytics, spam detection, image processing, scheduled publishing
- **v0.1.0** - Initial release with core CMS functionality

---

## Upgrade Guide

### From 0.1.0 to 0.2.0

1. **Run Migrations**
   ```bash
   php artisan migrate
   ```

2. **Update Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure Scheduler** (for scheduled posts)
   Add to your crontab:
   ```
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

4. **Seed Search Data** (optional, for development)
   ```bash
   php artisan db:seed --class=SearchLogSeeder
   ```

5. **Clear Caches**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

---

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
