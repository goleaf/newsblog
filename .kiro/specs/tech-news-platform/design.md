# Design Document

## Overview

TechNewsHub is a modern, full-featured news and blog platform built with Laravel 12, leveraging the framework's latest features and best practices. The system architecture follows a layered approach with clear separation of concerns, utilizing Laravel's MVC pattern, service layer for business logic, and repository pattern for data access where complexity warrants it.

The platform is designed to handle high traffic loads through aggressive caching strategies, database query optimization, and lazy loading of assets. The frontend uses a mobile-first responsive design built with Tailwind CSS v3 and Alpine.js for interactive components, with progressive enhancement for advanced features.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Frontend Layer                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Blade Views  │  │  Alpine.js   │  │ Tailwind CSS │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Controllers  │  │   Services   │  │  Resources   │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   Policies   │  │  Observers   │  │    Jobs      │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                      Domain Layer                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │    Models    │  │  Factories   │  │   Seeders    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                   Infrastructure Layer                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   SQLite DB  │  │  File System │  │    Cache     │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
```

### Technology Stack

- **Backend Framework**: Laravel 12
- **Database**: SQLite (with migration path to PostgreSQL/MySQL for production)
- **Frontend**: Blade Templates + Alpine.js v3
- **CSS Framework**: Tailwind CSS v3
- **Asset Bundling**: Vite
- **Caching**: File-based cache (Redis-ready)
- **Queue**: Database queue driver (Redis-ready)
- **Search**: Full-text search with SQLite FTS5
- **Admin Panel**: Laravel Nova 4


## Components and Interfaces

### Core Models

#### User Model
```php
class User extends Authenticatable
{
    // Attributes
    - id: int
    - name: string
    - email: string (unique)
    - password: string (hashed)
    - role: enum (admin, editor, author, user)
    - status: enum (active, suspended, inactive)
    - avatar: string (nullable)
    - bio: text (nullable)
    - two_factor_secret: string (nullable, encrypted)
    - two_factor_recovery_codes: text (nullable, encrypted)
    - last_login_at: timestamp (nullable)
    - email_verified_at: timestamp (nullable)
    - remember_token: string (nullable)
    - timestamps
    
    // Relationships
    - posts(): hasMany(Post)
    - comments(): hasMany(Comment)
    - bookmarks(): hasMany(Bookmark)
    - notifications(): hasMany(Notification)
    - activityLogs(): hasMany(ActivityLog)
    
    // Methods
    + isAdmin(): bool
    + isEditor(): bool
    + isAuthor(): bool
    + canPublish(): bool
    + hasTwoFactorEnabled(): bool
}
```

#### Post Model
```php
class Post extends Model
{
    // Attributes
    - id: int
    - user_id: int (foreign key)
    - series_id: int (nullable, foreign key)
    - series_order: int (nullable)
    - title: string
    - slug: string (unique)
    - excerpt: text (nullable)
    - content: longText
    - featured_image: string (nullable)
    - status: enum (draft, scheduled, published, archived)
    - published_at: timestamp (nullable)
    - scheduled_at: timestamp (nullable)
    - reading_time: int (minutes)
    - view_count: int (default 0)
    - is_featured: boolean (default false)
    - is_breaking: boolean (default false)
    - is_sponsored: boolean (default false)
    - is_editors_pick: boolean (default false)
    - meta_title: string (nullable)
    - meta_description: text (nullable)
    - meta_keywords: text (nullable)
    - timestamps
    - soft_deletes
    
    // Relationships
    - author(): belongsTo(User)
    - categories(): belongsToMany(Category)
    - tags(): belongsToMany(Tag)
    - comments(): hasMany(Comment)
    - revisions(): hasMany(PostRevision)
    - views(): hasMany(PostView)
    - bookmarks(): hasMany(Bookmark)
    - series(): belongsTo(Series)
    
    // Scopes
    + scopePublished($query)
    + scopeFeatured($query)
    + scopeBreaking($query)
    + scopeScheduled($query)
    + scopePopular($query)
    + scopeEditorsPicksOnly($query)
    + scopeSponsored($query)
    
    // Methods
    + isPublished(): bool
    + incrementViewCount(): void
    + calculateReadingTime(): int
    + generateSlug(): string
    + getNextInSeries(): ?Post
    + getPreviousInSeries(): ?Post
    + getSeriesProgress(): array
}
```

#### Category Model
```php
class Category extends Model
{
    // Attributes
    - id: int
    - parent_id: int (nullable, foreign key)
    - name: string
    - slug: string (unique)
    - description: text (nullable)
    - image: string (nullable)
    - order: int (default 0)
    - is_active: boolean (default true)
    - meta_title: string (nullable)
    - meta_description: text (nullable)
    - timestamps
    - soft_deletes
    
    // Relationships
    - parent(): belongsTo(Category)
    - children(): hasMany(Category)
    - posts(): belongsToMany(Post)
    
    // Methods
    + isParent(): bool
    + hasChildren(): bool
    + getPostCount(): int
}
```

#### Tag Model
```php
class Tag extends Model
{
    // Attributes
    - id: int
    - name: string
    - slug: string (unique)
    - description: text (nullable)
    - timestamps
    
    // Relationships
    - posts(): belongsToMany(Post)
    
    // Methods
    + getPostCount(): int
}
```

#### Comment Model
```php
class Comment extends Model
{
    // Attributes
    - id: int
    - post_id: int (foreign key)
    - user_id: int (nullable, foreign key)
    - parent_id: int (nullable, foreign key)
    - author_name: string (nullable)
    - author_email: string (nullable)
    - content: text
    - status: enum (pending, approved, spam, rejected)
    - ip_address: string
    - user_agent: text
    - timestamps
    
    // Relationships
    - post(): belongsTo(Post)
    - user(): belongsTo(User)
    - parent(): belongsTo(Comment)
    - replies(): hasMany(Comment)
    
    // Scopes
    + scopeApproved($query)
    + scopePending($query)
    + scopeSpam($query)
    
    // Methods
    + isApproved(): bool
    + isSpam(): bool
    + getNestingLevel(): int
}
```

#### Media Model
```php
class Media extends Model
{
    // Attributes
    - id: int
    - user_id: int (foreign key)
    - filename: string
    - original_filename: string
    - mime_type: string
    - size: int (bytes)
    - path: string
    - alt_text: string (nullable)
    - caption: text (nullable)
    - metadata: json (nullable)
    - timestamps
    
    // Relationships
    - user(): belongsTo(User)
    
    // Methods
    + getUrl(): string
    + getThumbnailUrl(): string
    + getMediumUrl(): string
    + getLargeUrl(): string
    + getWebPUrl(): string
    + isImage(): bool
    + hasAltText(): bool
}
```

#### Series Model
```php
class Series extends Model
{
    // Attributes
    - id: int
    - name: string
    - slug: string (unique)
    - description: text (nullable)
    - image: string (nullable)
    - timestamps
    
    // Relationships
    - posts(): hasMany(Post)
    
    // Methods
    + getPostCount(): int
    + getOrderedPosts(): Collection
}
```

#### Bookmark Model
```php
class Bookmark extends Model
{
    // Attributes
    - id: int
    - user_id: int (foreign key)
    - post_id: int (foreign key)
    - collection_id: int (nullable, foreign key)
    - timestamps
    
    // Relationships
    - user(): belongsTo(User)
    - post(): belongsTo(Post)
    - collection(): belongsTo(BookmarkCollection)
    
    // Unique constraint on (user_id, post_id)
}
```

#### BookmarkCollection Model
```php
class BookmarkCollection extends Model
{
    // Attributes
    - id: int
    - user_id: int (foreign key)
    - name: string
    - description: text (nullable)
    - is_public: boolean (default false)
    - timestamps
    
    // Relationships
    - user(): belongsTo(User)
    - bookmarks(): hasMany(Bookmark)
    
    // Methods
    + getBookmarkCount(): int
}
```

#### BrokenLink Model
```php
class BrokenLink extends Model
{
    // Attributes
    - id: int
    - post_id: int (foreign key)
    - url: string
    - status_code: int (nullable)
    - error_message: text (nullable)
    - status: enum (pending, fixed, ignored)
    - checked_at: timestamp
    - timestamps
    
    // Relationships
    - post(): belongsTo(Post)
    
    // Scopes
    + scopePending($query)
    + scopeFixed($query)
}
```

#### Poll Model
```php
class Poll extends Model
{
    // Attributes
    - id: int
    - question: string
    - options: json (array of options)
    - votes: json (array of vote counts)
    - end_date: timestamp (nullable)
    - is_active: boolean (default true)
    - timestamps
    
    // Methods
    + hasVoted(string $ip): bool
    + recordVote(int $optionIndex, string $ip): void
    + getResults(): array
    + isExpired(): bool
}
```

### Service Layer

#### PostService
```php
class PostService
{
    // Methods
    + create(array $data): Post
    + update(Post $post, array $data): Post
    + publish(Post $post): Post
    + schedule(Post $post, Carbon $date): Post
    + archive(Post $post): Post
    + incrementViewCount(Post $post, Request $request): void
    + getRelatedPosts(Post $post, int $limit = 4): Collection
    + calculateReadingTime(string $content): int
    + generateTableOfContents(string $content): array
    + extractHeadings(string $content): array
}
```

#### PostRevisionService
```php
class PostRevisionService
{
    // Methods
    + createRevision(Post $post): PostRevision
    + getRevisions(Post $post): Collection
    + compareRevisions(PostRevision $rev1, PostRevision $rev2): array
    + restoreRevision(Post $post, PostRevision $revision): Post
    + pruneOldRevisions(Post $post, int $maxRevisions = 25): void
}
```

#### SeriesNavigationService
```php
class SeriesNavigationService
{
    // Methods
    + getSeriesNavigation(Post $post): array
    + getSeriesProgress(Post $post): array
    + getNextPost(Post $post): ?Post
    + getPreviousPost(Post $post): ?Post
}
```

#### SearchService
```php
class SearchService
{
    // Methods
    + search(string $query, array $filters = []): Collection
    + autocomplete(string $query, int $limit = 5): Collection
    + logSearch(string $query, int $resultCount): void
    + getPopularSearches(int $limit = 10): Collection
    + voiceSearch(string $transcript): Collection
}
```

#### AdvancedSearchService
```php
class AdvancedSearchService
{
    // Methods
    + searchWithFilters(string $query, array $filters): Collection
    + filterByDateRange(Builder $query, Carbon $start, Carbon $end): Builder
    + filterByAuthor(Builder $query, int $authorId): Builder
    + filterByCategory(Builder $query, int $categoryId, bool $includeSubcategories = true): Builder
    + applyMultipleFilters(Builder $query, array $filters): Builder
}
```

#### CacheService
```php
class CacheService
{
    // Methods
    + rememberPost(int $postId, Closure $callback): Post
    + rememberCategory(int $categoryId, Closure $callback): Category
    + rememberSettings(): Collection
    + clearPostCache(Post $post): void
    + clearCategoryCache(Category $category): void
    + clearAllCache(): void
}
```

#### ImageProcessingService
```php
class ImageProcessingService
{
    // Methods
    + upload(UploadedFile $file): Media
    + generateVariants(Media $media): void
    + optimize(string $path): void
    + convertToWebP(string $path): string
    + stripExif(string $path): void
    + resize(string $path, int $width, int $height): void
    + generateQRCode(string $url): string
}
```

#### AltTextValidator
```php
class AltTextValidator
{
    // Methods
    + validatePostImages(Post $post): array
    + findImagesWithoutAltText(string $content): array
    + generateAccessibilityReport(): Collection
    + bulkUpdateAltText(array $updates): void
}
```

#### NotificationService
```php
class NotificationService
{
    // Methods
    + notifyCommentApproved(Comment $comment): void
    + notifyCommentReply(Comment $comment): void
    + notifyPostPublished(Post $post): void
    + notifyScheduledPost(Post $post): void
    + sendWelcomeEmail(User $user): void
}
```

#### SpamDetectionService
```php
class SpamDetectionService
{
    // Methods
    + isSpam(string $content, string $email, string $ip): bool
    + checkLinkCount(string $content): bool
    + checkBlacklist(string $content): bool
    + checkSubmissionSpeed(string $ip): bool
    + checkHoneypot(Request $request): bool
    + blockIp(string $ip, int $minutes): void
}
```

#### BrokenLinkChecker
```php
class BrokenLinkChecker
{
    // Methods
    + scanPost(Post $post): array
    + checkUrl(string $url): array
    + markAsBroken(Post $post, string $url, int $statusCode): BrokenLink
    + generateReport(): Collection
    + fixBrokenLink(BrokenLink $link, string $newUrl): void
}
```

#### GdprService
```php
class GdprService
{
    // Methods
    + exportUserData(User $user): array
    + anonymizeUser(User $user): void
    + deleteUserData(User $user): void
    + recordConsent(string $ip, array $preferences): void
    + withdrawConsent(string $ip): void
}
```

#### PerformanceMetricsService
```php
class PerformanceMetricsService
{
    // Methods
    + recordPageLoad(string $url, float $loadTime): void
    + recordQueryTime(string $query, float $executionTime): void
    + getCacheHitRate(int $days = 7): array
    + getSlowQueries(int $threshold = 100): Collection
    + getAverageLoadTime(int $hours = 24): float
    + getMemoryUsage(): array
}
```

#### SitemapService
```php
class SitemapService
{
    // Methods
    + generate(): void
    + addPost(Post $post): void
    + addCategory(Category $category): void
    + addPage(Page $page): void
    + addTag(Tag $tag): void
    + splitSitemap(int $maxUrls = 50000): array
}
```

### Controllers

#### Frontend Controllers

**HomeController**
- index(): Display homepage with featured posts, breaking news, and category sections
- search(): Handle search requests with filters

**PostController**
- index(): List all published posts with pagination
- show(Post $post): Display single post with comments and related posts
- incrementView(Post $post): Track post views via AJAX

**CategoryController**
- show(Category $category): Display category page with filtered posts

**TagController**
- show(Tag $tag): Display tag page with associated posts

**CommentController**
- store(Request $request, Post $post): Submit new comment
- reply(Request $request, Comment $comment): Reply to comment

**BookmarkController**
- index(): Display user's bookmarked posts
- store(Post $post): Add post to bookmarks
- destroy(Post $post): Remove post from bookmarks
- toggle(Post $post): Toggle bookmark status via AJAX

**BookmarkCollectionController**
- index(): List user's bookmark collections
- store(Request $request): Create new collection
- update(BookmarkCollection $collection): Update collection
- destroy(BookmarkCollection $collection): Delete collection
- addPost(BookmarkCollection $collection, Post $post): Add post to collection

**SeriesController**
- show(Series $series): Display series landing page with all posts
- progress(Series $series, Post $post): Track user progress in series

**NewsletterController**
- subscribe(Request $request): Handle newsletter subscription
- verify(string $token): Verify email subscription
- unsubscribe(string $token): Handle unsubscribe requests
- export(): Export verified subscribers as CSV

**NotificationController**
- index(): Display user notifications
- markAsRead(Notification $notification): Mark single notification as read
- markAllAsRead(): Mark all notifications as read
- destroy(Notification $notification): Delete notification

**GdprController**
- exportData(): Export user's personal data
- deleteAccount(): Request account deletion
- consent(): Display and manage cookie consent preferences

**ReadingHistoryController**
- index(): Display user's reading history
- track(Post $post): Record post view in history
- clear(): Clear all reading history

#### Admin Controllers (Nova Resources)

**PostResource**
- fields(): Define post fields for Nova
- filters(): Category, tag, status, author, series filters
- actions(): Publish, schedule, archive, mark as breaking, mark as editor's pick actions
- lenses(): Popular posts, scheduled posts, breaking news, editor's picks

**SeriesResource**
- fields(): Define series fields with post relationship
- actions(): Reorder posts in series

**BrokenLinkResource**
- fields(): Define broken link fields with post reference
- filters(): Status filter
- actions(): Mark as fixed, mark as ignored, bulk fix

**PollResource**
- fields(): Define poll fields with options and results
- actions(): Activate, deactivate, reset votes

**CategoryResource**
- fields(): Define category fields with parent selector
- actions(): Reorder categories

**UserResource**
- fields(): Define user fields with role selector
- filters(): Role, status filters
- actions(): Suspend user, reset password

**MediaResource**
- fields(): Define media fields with preview
- actions(): Bulk delete, regenerate thumbnails

### View Components

#### Layout Components

**Header Component**
```blade
<x-layout.header>
    - Logo
    - Main navigation menu
    - Search bar
    - User account dropdown
    - Dark mode toggle
    - Mobile menu button
</x-layout.header>
```

**Footer Component**
```blade
<x-layout.footer>
    - Footer navigation
    - Newsletter signup form
    - Social media links
    - Copyright information
</x-layout.footer>
```

**Sidebar Component**
```blade
<x-layout.sidebar>
    - Dynamic widget areas
    - Recent posts widget
    - Popular posts widget
    - Categories widget
    - Tags cloud widget
    - Newsletter widget
</x-layout.sidebar>
```

#### Content Components

**Post Card Component**
```blade
<x-post-card :post="$post">
    - Featured image with lazy loading
    - Category badge
    - Title with link
    - Excerpt
    - Author info
    - Publication date
    - Reading time
    - View count
    - Bookmark button
</x-post-card>
```

**Comment Component**
```blade
<x-comment :comment="$comment">
    - Author avatar
    - Author name
    - Comment content
    - Timestamp
    - Reply button
    - Nested replies (recursive)
</x-comment>
```

**Breaking News Ticker Component**
```blade
<x-breaking-news-ticker>
    - Scrolling news items
    - Auto-rotation every 5 seconds
    - Click to view full article
    - Distinctive background color and icon
    - Auto-remove items older than 24 hours
</x-breaking-news-ticker>
```

**Series Navigation Component**
```blade
<x-series-navigation :post="$post">
    - Previous/Next post links
    - Series progress indicator
    - Current position in series
    - Link to series landing page
</x-series-navigation>
```

**Reading Progress Bar Component**
```blade
<x-reading-progress-bar>
    - Fixed position at top
    - Percentage-based width
    - Smooth animation
    - Based on article content height
</x-reading-progress-bar>
```

**Font Size Controls Component**
```blade
<x-font-size-controls>
    - Increase button
    - Decrease button
    - Reset button
    - Current size percentage display
    - LocalStorage persistence
</x-font-size-controls>
```

**Image Lightbox Component**
```blade
<x-image-lightbox>
    - Full-size image display
    - Navigation arrows for galleries
    - Caption display
    - Close on ESC or outside click
    - Pinch-to-zoom support
</x-image-lightbox>
```

**Poll Widget Component**
```blade
<x-poll-widget :poll="$poll">
    - Question display
    - Answer options with radio buttons
    - Vote button
    - Results with percentage bars
    - Vote count display
    - Expired state handling
</x-poll-widget>
```

**Countdown Timer Component**
```blade
<x-countdown-timer :targetDate="$date">
    - Days, hours, minutes, seconds
    - Real-time JavaScript updates
    - Completion message
    - Customizable labels
</x-countdown-timer>
```

**Scroll to Top Button Component**
```blade
<x-scroll-to-top>
    - Fixed bottom-right position
    - Appears after 300px scroll
    - Smooth scroll animation
    - Fade in/out transitions
</x-scroll-to-top>
```

**Live Updates Feed Component**
```blade
<x-live-updates-feed>
    - WebSocket connection
    - New post notifications
    - "View new post" button
    - Badge count for multiple updates
    - Auto-reconnect on disconnect
</x-live-updates-feed>
```

**Skeleton Loading Component**
```blade
<x-skeleton-loader :type="$type">
    - Post card skeleton
    - Article content skeleton
    - Sidebar widget skeleton
    - Shimmer animation effect
</x-skeleton-loader>
```

**Table of Contents Component**
```blade
<x-table-of-contents :post="$post">
    - Auto-generated from headings
    - Sticky positioning
    - Active section highlighting
    - Smooth scroll navigation
</x-table-of-contents>
```

### API Endpoints

#### Public API (Rate Limited: 60 requests/minute)

```
GET    /api/posts                    - List published posts
GET    /api/posts/{slug}             - Get single post
GET    /api/categories               - List categories
GET    /api/categories/{slug}/posts  - Get category posts
GET    /api/tags                     - List tags
GET    /api/tags/{slug}/posts        - Get tag posts
GET    /api/search                   - Search posts
```

#### Authenticated API (Rate Limited: 120 requests/minute)

```
POST   /api/posts                    - Create post (Editor/Admin)
PUT    /api/posts/{id}               - Update post
DELETE /api/posts/{id}               - Delete post
POST   /api/comments                 - Create comment
POST   /api/bookmarks                - Add bookmark
DELETE /api/bookmarks/{id}           - Remove bookmark
```

### Database Schema Design

#### Core Tables

**users**
- Primary key: id
- Indexes: email (unique), role, status
- Foreign keys: None

**posts**
- Primary key: id
- Indexes: slug (unique), user_id, status, published_at, is_featured, is_breaking
- Foreign keys: user_id → users(id)
- Full-text index: title, content, excerpt

**categories**
- Primary key: id
- Indexes: slug (unique), parent_id, order
- Foreign keys: parent_id → categories(id)

**tags**
- Primary key: id
- Indexes: slug (unique)
- Foreign keys: None

**comments**
- Primary key: id
- Indexes: post_id, user_id, parent_id, status
- Foreign keys: post_id → posts(id), user_id → users(id), parent_id → comments(id)

**media**
- Primary key: id
- Indexes: user_id, mime_type
- Foreign keys: user_id → users(id)

#### Pivot Tables

**category_post**
- Composite primary key: (category_id, post_id)
- Indexes: category_id, post_id
- Foreign keys: category_id → categories(id), post_id → posts(id)

**post_tag**
- Composite primary key: (post_id, tag_id)
- Indexes: post_id, tag_id
- Foreign keys: post_id → posts(id), tag_id → tags(id)

#### Supporting Tables

**newsletters**
- Primary key: id
- Indexes: email (unique), status, verification_token
- Foreign keys: None

**post_views**
- Primary key: id
- Indexes: post_id, session_id, created_at
- Foreign keys: post_id → posts(id)

**bookmarks**
- Primary key: id
- Indexes: user_id, post_id, composite (user_id, post_id) unique
- Foreign keys: user_id → users(id), post_id → posts(id)

**post_revisions**
- Primary key: id
- Indexes: post_id, user_id, created_at
- Foreign keys: post_id → posts(id), user_id → users(id)

**notifications**
- Primary key: id
- Indexes: user_id, read_at, created_at
- Foreign keys: user_id → users(id)

**activity_logs**
- Primary key: id
- Indexes: user_id, subject_type, subject_id, created_at
- Foreign keys: user_id → users(id)

**settings**
- Primary key: id
- Indexes: key (unique)
- Foreign keys: None

**series**
- Primary key: id
- Indexes: slug (unique)
- Foreign keys: None

**broken_links**
- Primary key: id
- Indexes: post_id, status, checked_at
- Foreign keys: post_id → posts(id)


## Data Models

### Post Content Structure

Posts use a rich content structure stored as HTML with embedded components:

```html
<article class="prose">
    <h2>Section Heading</h2>
    <p>Regular paragraph content...</p>
    
    <!-- Pull Quote -->
    <blockquote class="pull-quote">
        <p>Important quote text</p>
        <cite>Attribution</cite>
    </blockquote>
    
    <!-- Image with Caption -->
    <figure>
        <img src="..." alt="..." loading="lazy" />
        <figcaption>Image caption</figcaption>
    </figure>
    
    <!-- Photo Gallery -->
    <div class="gallery" data-gallery-id="1">
        <img src="..." alt="..." />
        <img src="..." alt="..." />
    </div>
    
    <!-- Embedded Chart -->
    <div class="chart" data-chart-type="line" data-chart-data="...">
    </div>
    
    <!-- Poll Widget -->
    <div class="poll" data-poll-id="123">
    </div>
    
    <!-- Code Block -->
    <pre><code class="language-php">
    // Code content
    </code></pre>
</article>
```

### Settings Structure

Settings are stored as key-value pairs with JSON values for complex settings:

```json
{
    "site_name": "TechNewsHub",
    "site_tagline": "Your Source for Tech News",
    "posts_per_page": 15,
    "comments_enabled": true,
    "comments_require_approval": true,
    "newsletter_enabled": true,
    "breaking_news_duration_hours": 24,
    "cache_duration_minutes": 10,
    "social_links": {
        "twitter": "https://twitter.com/...",
        "facebook": "https://facebook.com/...",
        "linkedin": "https://linkedin.com/..."
    },
    "seo_settings": {
        "meta_title_template": "{title} | {site_name}",
        "meta_description_default": "...",
        "og_image_default": "/images/og-default.jpg"
    }
}
```

### Cache Keys Structure

```
// Post caching
post:{id}
post:slug:{slug}
post:related:{id}
post:views:{id}

// Category caching
category:{id}
category:slug:{slug}
category:posts:{id}
category:tree

// Settings caching
settings:all
settings:{key}

// Widget caching
widget:{area}:{position}

// Search caching
search:{query}:{filters_hash}
```

## Error Handling

### Exception Hierarchy

```
App\Exceptions\
├── PostNotFoundException
├── CategoryNotFoundException
├── UnauthorizedActionException
├── InvalidMediaTypeException
├── SpamDetectedException
├── RateLimitExceededException
└── MaintenanceModeException
```

### Error Responses

**API Error Response Format**
```json
{
    "message": "Human-readable error message",
    "errors": {
        "field_name": [
            "Validation error message"
        ]
    },
    "code": "ERROR_CODE",
    "status": 422
}
```

**Frontend Error Handling**
- 404: Custom not found page with search and popular posts
- 500: Generic error page with contact information
- 503: Maintenance mode page with estimated return time
- 429: Rate limit exceeded page with retry information

### Validation Rules

**Post Validation**
```php
[
    'title' => 'required|string|max:255',
    'slug' => 'required|string|max:255|unique:posts,slug,' . $post->id,
    'content' => 'required|string',
    'excerpt' => 'nullable|string|max:500',
    'status' => 'required|in:draft,scheduled,published,archived',
    'published_at' => 'nullable|date',
    'scheduled_at' => 'nullable|date|after:now',
    'categories' => 'required|array|min:1',
    'categories.*' => 'exists:categories,id',
    'tags' => 'nullable|array',
    'tags.*' => 'exists:tags,id',
    'featured_image' => 'nullable|image|max:10240',
    'meta_title' => 'nullable|string|max:60',
    'meta_description' => 'nullable|string|max:160',
]
```

**Comment Validation**
```php
[
    'content' => 'required|string|min:10|max:1000',
    'author_name' => 'required_without:user_id|string|max:100',
    'author_email' => 'required_without:user_id|email|max:255',
    'parent_id' => 'nullable|exists:comments,id',
]
```

**Media Upload Validation**
```php
[
    'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:10240',
    'alt_text' => 'nullable|string|max:255',
    'caption' => 'nullable|string|max:500',
]
```

## Testing Strategy

### Unit Tests

**Model Tests**
- Test model relationships
- Test scopes and query builders
- Test attribute accessors and mutators
- Test model methods

**Service Tests**
- Test business logic in isolation
- Mock external dependencies
- Test edge cases and error conditions

**Helper Tests**
- Test utility functions
- Test string manipulation
- Test date formatting

### Feature Tests

**Authentication Tests**
- Test login/logout flow
- Test registration
- Test password reset
- Test 2FA flow
- Test role-based access control

**Post Management Tests**
- Test post creation
- Test post publishing
- Test post scheduling
- Test post archiving
- Test slug generation
- Test reading time calculation

**Comment Tests**
- Test comment submission
- Test comment approval
- Test spam detection
- Test nested replies
- Test comment moderation

**Search Tests**
- Test search functionality
- Test autocomplete
- Test filters
- Test result ranking

**API Tests**
- Test all API endpoints
- Test authentication
- Test rate limiting
- Test error responses
- Test pagination

### Browser Tests (Dusk)

**Critical User Flows**
- Complete post creation and publication flow
- Comment submission and approval flow
- Newsletter subscription flow
- Bookmark management flow
- Search and filter flow

**Interactive Features**
- Test infinite scroll
- Test lazy loading
- Test modal interactions
- Test form submissions
- Test AJAX updates

### Performance Tests

**Load Testing**
- Homepage load under concurrent users
- Post page load with comments
- Search performance with large datasets
- API endpoint response times

**Database Query Tests**
- N+1 query detection
- Slow query identification
- Index effectiveness

### Accessibility Tests

**WCAG 2.1 AA Compliance**
- Keyboard navigation
- Screen reader compatibility
- Color contrast ratios
- Alt text presence
- ARIA attributes
- Focus management

## Security Considerations

### Authentication & Authorization

**Password Security**
- Minimum 8 characters
- Bcrypt hashing with cost factor 12
- Password reset tokens expire after 1 hour
- Account lockout after 5 failed attempts

**Session Management**
- Secure, HTTP-only cookies
- Session timeout after 120 minutes of inactivity
- CSRF protection on all forms
- Session regeneration on login

**Two-Factor Authentication**
- TOTP-based (Google Authenticator compatible)
- Backup codes for account recovery
- "Remember device" option for 30 days
- Rate limiting on 2FA attempts

### Input Validation & Sanitization

**XSS Prevention**
- Escape all user-generated content
- Use Blade's {{ }} syntax for automatic escaping
- Sanitize HTML content with HTMLPurifier
- Content Security Policy headers

**SQL Injection Prevention**
- Use Eloquent ORM exclusively
- Parameterized queries for raw SQL
- Input validation on all database operations

**File Upload Security**
- Validate MIME types
- Restrict file extensions
- Store uploads outside web root
- Generate unique filenames
- Scan for malware (optional)

### API Security

**Rate Limiting**
- Public endpoints: 60 requests/minute per IP
- Authenticated endpoints: 120 requests/minute per user
- Sliding window algorithm
- Custom rate limits for specific endpoints

**Authentication**
- Laravel Sanctum for API tokens
- Token expiration after 30 days
- Ability to revoke tokens
- Separate tokens for different applications

### Data Protection

**GDPR Compliance**
- Cookie consent banner
- Data export functionality
- Right to be forgotten (account deletion)
- Privacy policy page
- Data retention policies

**Encryption**
- Encrypt sensitive data at rest
- HTTPS for all connections
- Encrypt 2FA secrets
- Encrypt backup codes

### Security Headers

```php
// Middleware to set security headers
'X-Frame-Options' => 'SAMEORIGIN',
'X-Content-Type-Options' => 'nosniff',
'X-XSS-Protection' => '1; mode=block',
'Referrer-Policy' => 'strict-origin-when-cross-origin',
'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';"
```

## Performance Optimization

### Caching Strategy

**Page Caching**
- Homepage: 10 minutes
- Category pages: 15 minutes
- Tag pages: 15 minutes
- Static pages: 1 hour
- Post pages: 1 hour (cleared on update)

**Query Caching**
- Popular posts: 1 hour
- Recent posts: 10 minutes
- Category tree: 1 day
- Settings: 24 hours
- Menu items: 1 day

**Fragment Caching**
- Sidebar widgets: 30 minutes
- Footer content: 1 day
- Navigation menu: 1 day
- Breaking news ticker: 5 minutes

### Database Optimization

**Eager Loading**
```php
// Always eager load relationships
Post::with(['author', 'categories', 'tags'])
    ->published()
    ->latest()
    ->paginate(15);
```

**Query Optimization**
- Use select() to limit columns
- Use chunk() for large datasets
- Use cursor() for memory-efficient iteration
- Add indexes on frequently queried columns

**Connection Pooling**
- Configure persistent connections
- Set appropriate pool size
- Monitor connection usage

### Asset Optimization

**CSS Optimization**
- PurgeCSS to remove unused styles
- Minification in production
- Critical CSS inline for above-the-fold content
- Defer non-critical CSS

**JavaScript Optimization**
- Code splitting by route
- Lazy load non-critical scripts
- Minification and compression
- Tree shaking to remove unused code

**Image Optimization**
- Lazy loading for below-the-fold images
- Responsive images with srcset
- WebP format with JPEG fallback
- Image compression (85% quality)
- CDN delivery (optional)

### Frontend Performance

**Loading Strategy**
- Critical CSS inline
- Defer JavaScript loading
- Preload key resources
- DNS prefetch for external domains

**Rendering Optimization**
- Minimize DOM manipulation
- Use CSS transforms for animations
- Debounce scroll and resize events
- Virtual scrolling for long lists

**Metrics Targets**
- First Contentful Paint: < 1.8s
- Largest Contentful Paint: < 2.5s
- Time to Interactive: < 3.8s
- Cumulative Layout Shift: < 0.1
- First Input Delay: < 100ms

## Deployment Architecture

### Environment Configuration

**Development**
- SQLite database
- File-based cache
- Database queue driver
- Debug mode enabled
- Detailed error pages

**Staging**
- SQLite or PostgreSQL
- Redis cache
- Redis queue driver
- Debug mode disabled
- Error logging to file

**Production**
- PostgreSQL or MySQL
- Redis cache with replication
- Redis queue with Horizon
- Debug mode disabled
- Error logging to external service (Sentry)
- CDN for static assets
- Load balancer for multiple app servers

### Deployment Process

1. Run tests (PHPUnit, Dusk)
2. Build assets (npm run build)
3. Deploy code to server
4. Run migrations (php artisan migrate --force)
5. Clear and warm cache
6. Restart queue workers
7. Run smoke tests
8. Monitor error logs

### Backup Strategy

**Database Backups**
- Daily full backups at 2:00 AM
- Retain for 30 days
- Store in cloud storage (S3, DO Spaces)
- Test restore process monthly

**Media Backups**
- Weekly full backups
- Incremental daily backups
- Store in cloud storage
- Retain for 90 days

### Monitoring

**Application Monitoring**
- Error tracking (Sentry, Bugsnag)
- Performance monitoring (New Relic, Scout)
- Uptime monitoring (Pingdom, UptimeRobot)
- Log aggregation (Papertrail, Loggly)

**Infrastructure Monitoring**
- Server resources (CPU, memory, disk)
- Database performance
- Cache hit rates
- Queue processing times

**Alerts**
- Error rate threshold exceeded
- Response time degradation
- Disk space low
- Queue backlog growing
- Database connection issues

## Frontend Design System

### Typography

**Font Stack**
```css
--font-sans: 'Inter', system-ui, -apple-system, sans-serif;
--font-serif: 'Merriweather', Georgia, serif;
--font-mono: 'Fira Code', 'Courier New', monospace;
```

**Type Scale**
- xs: 0.75rem (12px)
- sm: 0.875rem (14px)
- base: 1rem (16px)
- lg: 1.125rem (18px)
- xl: 1.25rem (20px)
- 2xl: 1.5rem (24px)
- 3xl: 1.875rem (30px)
- 4xl: 2.25rem (36px)
- 5xl: 3rem (48px)

### Color Palette

**Brand Colors**
```css
--primary-50: #eff6ff;
--primary-500: #3b82f6;
--primary-900: #1e3a8a;

--secondary-50: #f8fafc;
--secondary-500: #64748b;
--secondary-900: #0f172a;
```

**Semantic Colors**
```css
--success: #10b981;
--warning: #f59e0b;
--error: #ef4444;
--info: #3b82f6;
```

**Dark Mode**
```css
--bg-primary-dark: #0f172a;
--bg-secondary-dark: #1e293b;
--text-primary-dark: #f1f5f9;
--text-secondary-dark: #cbd5e1;
```

### Spacing System

Based on 4px base unit:
- 0: 0
- 1: 0.25rem (4px)
- 2: 0.5rem (8px)
- 3: 0.75rem (12px)
- 4: 1rem (16px)
- 6: 1.5rem (24px)
- 8: 2rem (32px)
- 12: 3rem (48px)
- 16: 4rem (64px)

### Breakpoints

```css
--screen-sm: 640px;
--screen-md: 768px;
--screen-lg: 1024px;
--screen-xl: 1280px;
--screen-2xl: 1536px;
```

### Component Patterns

**Button Variants**
- Primary: Solid background, high contrast
- Secondary: Outline style
- Ghost: Transparent background
- Link: Text-only, underline on hover

**Card Patterns**
- Post card: Image, title, excerpt, metadata
- Comment card: Avatar, content, actions
- Widget card: Title, content, optional footer

**Form Patterns**
- Input fields with floating labels
- Inline validation messages
- Loading states
- Success/error feedback

### Animation Guidelines

**Timing Functions**
- ease-in: Accelerating
- ease-out: Decelerating (preferred for UI)
- ease-in-out: Smooth start and end

**Duration**
- Micro-interactions: 150ms
- Component transitions: 300ms
- Page transitions: 500ms

**Reduced Motion**
- Respect prefers-reduced-motion
- Disable parallax and complex animations
- Use simple fade transitions

## Accessibility Implementation

### Keyboard Navigation

**Focus Management**
- Visible focus indicators (2px outline)
- Logical tab order
- Skip to main content link
- Focus trap in modals

**Keyboard Shortcuts**
- / : Focus search
- Esc: Close modals/overlays
- N/P: Next/previous page
- ?: Show keyboard shortcuts help

### Screen Reader Support

**ARIA Landmarks**
```html
<header role="banner">
<nav role="navigation" aria-label="Main">
<main role="main">
<aside role="complementary">
<footer role="contentinfo">
```

**ARIA Labels**
- Descriptive labels for interactive elements
- aria-label for icon-only buttons
- aria-describedby for form hints
- aria-live for dynamic content updates

**Semantic HTML**
- Use proper heading hierarchy (h1-h6)
- Use <article>, <section>, <nav>
- Use <button> for actions, <a> for navigation
- Use <label> for form inputs

### Color Contrast

**WCAG AA Requirements**
- Normal text: 4.5:1 minimum
- Large text (18pt+): 3:1 minimum
- UI components: 3:1 minimum

**Testing Tools**
- Automated: axe DevTools, Lighthouse
- Manual: Color contrast analyzer
- User testing: Screen reader testing

### Alternative Text

**Image Alt Text Guidelines**
- Descriptive for informative images
- Empty alt="" for decorative images
- Avoid "image of" or "picture of"
- Include relevant context

## Integration Points

### Third-Party Services

**Email Service**
- Provider: Mailgun, SendGrid, or SES
- Transactional emails: Welcome, notifications, password reset
- Marketing emails: Newsletter campaigns
- Email templates: Blade-based, responsive

**Analytics**
- Google Analytics 4
- Custom event tracking
- Privacy-compliant implementation
- Cookie consent integration

**CDN**
- CloudFlare, AWS CloudFront, or DigitalOcean Spaces
- Static asset delivery
- Image optimization
- DDoS protection

**Search Enhancement (Optional)**
- Algolia or Meilisearch
- Real-time indexing
- Advanced filtering
- Typo tolerance

**Social Media**
- Open Graph meta tags
- Twitter Card meta tags
- Social sharing buttons
- Embed support (Twitter, Facebook, Instagram)

### Webhook Support

**Outgoing Webhooks**
- Post published event
- Comment approved event
- User registered event
- Configurable endpoints
- Retry logic with exponential backoff

**Incoming Webhooks**
- Newsletter subscription confirmations
- Payment notifications (if monetization added)
- Third-party integrations

## Migration & Import

### WordPress Import

**Supported Data**
- Posts with content, metadata
- Categories and tags
- Authors (mapped to users)
- Comments
- Featured images
- Custom fields (mapped to metadata)

**Import Process**
1. Upload WordPress XML export
2. Parse and validate data
3. Map categories and tags
4. Create users for authors
5. Import posts with relationships
6. Import comments
7. Download and import media
8. Generate slugs and permalinks
9. Validate imported data

### Export Functionality

**Export Formats**
- JSON: Complete data export
- CSV: Posts, users, comments
- Markdown: Posts with frontmatter
- WordPress XML: For migration to WordPress

**Export Options**
- Select date range
- Filter by category/tag
- Include/exclude comments
- Include/exclude media

This design document provides a comprehensive blueprint for implementing all 75 requirements of the TechNewsHub platform. The architecture is scalable, maintainable, and follows Laravel best practices while ensuring performance, security, and accessibility.
