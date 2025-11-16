# Design Document

## Overview

This design document outlines the technical architecture and implementation strategy for a comprehensive technology news and programming magazine platform. The platform is built on Laravel 12 with a modern tech stack including Alpine.js for frontend interactivity, Tailwind CSS for styling, and a robust backend architecture supporting high-traffic content delivery, user engagement, and advanced features like AI-powered recommendations.

The design follows Laravel best practices, emphasizes performance through caching strategies, implements security-first principles, and ensures scalability through modular architecture and cloud-native deployment patterns.

## Architecture

### High-Level Architecture

The platform follows a layered MVC architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────┐
│                     Presentation Layer                   │
│  (Blade Templates, Alpine.js, Tailwind CSS)             │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                    Application Layer                     │
│  (Controllers, Form Requests, Resources, Middleware)    │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                      Business Layer                      │
│  (Services, Actions, Jobs, Events, Policies)            │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                       Data Layer                         │
│  (Models, Repositories, Database, Cache, Search)        │
└─────────────────────────────────────────────────────────┘
```

### Technology Stack

**Backend:**
- Laravel 12 (PHP 8.4) - Core framework
- MySQL 8.0 - Primary database
- Redis - Caching and session storage
- Laravel Sanctum - API authentication
- Laravel Queue - Background job processing
- Laravel Scout - Full-text search (with Meilisearch driver)

**Frontend:**
- Blade Templates - Server-side rendering
- Alpine.js 3 - Reactive components
- Tailwind CSS 3 - Utility-first styling
- Vite - Asset bundling

**Infrastructure:**
- Laravel Sail - Local development
- AWS S3 - Media storage
- CloudFront - CDN for static assets
- Laravel Horizon - Queue monitoring
- Laravel Telescope - Debugging (development only)


## Components and Interfaces

### 1. Content Management System

**Components:**
- `ArticleController` - Handles CRUD operations for articles
- `ArticleService` - Business logic for article management
- `ArticleRepository` - Data access layer
- `RichTextEditor` - Blade component with TipTap integration
- `MediaUploadHandler` - Service for image processing and storage

**Key Interfaces:**

```php
interface ArticleRepositoryInterface
{
    public function create(array $data): Article;
    public function update(Article $article, array $data): Article;
    public function publish(Article $article): bool;
    public function findBySlug(string $slug): ?Article;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}

interface MediaServiceInterface
{
    public function upload(UploadedFile $file, string $path): string;
    public function resize(string $path, int $width, int $height): string;
    public function delete(string $path): bool;
}
```

**Database Schema:**

```sql
articles
- id (bigint, primary key)
- title (varchar 255)
- slug (varchar 255, unique, indexed)
- excerpt (text)
- content (longtext)
- featured_image (varchar 255, nullable)
- author_id (bigint, foreign key -> users.id)
- category_id (bigint, foreign key -> categories.id)
- status (enum: draft, published, archived)
- published_at (timestamp, nullable, indexed)
- reading_time (int) // in minutes
- view_count (bigint, default 0)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)

categories
- id (bigint, primary key)
- name (varchar 100)
- slug (varchar 100, unique, indexed)
- description (text, nullable)
- parent_id (bigint, nullable, foreign key -> categories.id)
- order (int, default 0)
- created_at (timestamp)
- updated_at (timestamp)

article_tag
- article_id (bigint, foreign key -> articles.id)
- tag_id (bigint, foreign key -> tags.id)
- primary key (article_id, tag_id)

tags
- id (bigint, primary key)
- name (varchar 50, unique)
- slug (varchar 50, unique, indexed)
- usage_count (int, default 0)
- created_at (timestamp)
- updated_at (timestamp)
```

### 2. User Authentication and Authorization

**Components:**
- `AuthController` - Registration, login, logout
- `PasswordResetController` - Password recovery
- `SocialAuthController` - OAuth integration
- `UserPolicy` - Authorization rules
- `AuthMiddleware` - Custom authentication checks

**Authentication Flow:**

```
Registration:
1. User submits form → RegisterRequest validates
2. CreateUserAction creates user with hashed password
3. SendVerificationEmail job dispatched
4. User clicks verification link
5. VerifyEmailController marks email as verified
6. User redirected to dashboard

Login:
1. User submits credentials → LoginRequest validates
2. Auth::attempt() checks credentials
3. Session created with remember token (optional)
4. LoginEvent dispatched for logging
5. User redirected to intended page

OAuth:
1. User clicks social login button
2. Redirect to OAuth provider
3. Provider redirects back with code
4. Exchange code for access token
5. Fetch user profile from provider
6. FindOrCreateUser action
7. Login user and redirect
```

**Database Schema:**

```sql
users
- id (bigint, primary key)
- name (varchar 255)
- email (varchar 255, unique, indexed)
- email_verified_at (timestamp, nullable)
- password (varchar 255)
- remember_token (varchar 100, nullable)
- avatar (varchar 255, nullable)
- bio (text, nullable)
- role (enum: reader, author, moderator, admin)
- is_active (boolean, default true)
- last_login_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)

social_accounts
- id (bigint, primary key)
- user_id (bigint, foreign key -> users.id)
- provider (varchar 50) // google, github, twitter
- provider_id (varchar 255)
- provider_token (text, nullable)
- created_at (timestamp)
- updated_at (timestamp)
- unique (provider, provider_id)

password_reset_tokens
- email (varchar 255, primary key)
- token (varchar 255)
- created_at (timestamp)
```


### 3. User Profile Management

**Components:**
- `ProfileController` - Profile viewing and editing
- `ProfileUpdateService` - Profile update logic
- `AvatarUploadService` - Avatar processing
- `UserPreferencesService` - Preference management

**Database Schema:**

```sql
user_profiles
- user_id (bigint, primary key, foreign key -> users.id)
- website (varchar 255, nullable)
- twitter_handle (varchar 50, nullable)
- github_username (varchar 50, nullable)
- linkedin_url (varchar 255, nullable)
- location (varchar 100, nullable)
- company (varchar 100, nullable)
- job_title (varchar 100, nullable)
- created_at (timestamp)
- updated_at (timestamp)

user_preferences
- user_id (bigint, primary key, foreign key -> users.id)
- email_notifications (boolean, default true)
- comment_notifications (boolean, default true)
- newsletter_frequency (enum: daily, weekly, monthly, never)
- theme (enum: light, dark, auto)
- reading_list_public (boolean, default false)
- profile_visibility (enum: public, private, followers)
- created_at (timestamp)
- updated_at (timestamp)
```

### 4. Comment System

**Components:**
- `CommentController` - Comment CRUD operations
- `CommentService` - Comment business logic
- `CommentModerationService` - Moderation logic
- `CommentNotificationJob` - Async notifications
- `CommentPolicy` - Authorization rules

**Database Schema:**

```sql
comments
- id (bigint, primary key)
- article_id (bigint, foreign key -> articles.id, indexed)
- user_id (bigint, foreign key -> users.id)
- parent_id (bigint, nullable, foreign key -> comments.id)
- content (text)
- status (enum: pending, approved, rejected, flagged)
- moderation_reason (text, nullable)
- moderated_by (bigint, nullable, foreign key -> users.id)
- moderated_at (timestamp, nullable)
- created_at (timestamp, indexed)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)

comment_reactions
- id (bigint, primary key)
- comment_id (bigint, foreign key -> comments.id)
- user_id (bigint, foreign key -> users.id)
- reaction_type (enum: like, helpful, insightful)
- created_at (timestamp)
- unique (comment_id, user_id, reaction_type)

comment_flags
- id (bigint, primary key)
- comment_id (bigint, foreign key -> comments.id)
- user_id (bigint, foreign key -> users.id)
- reason (enum: spam, offensive, off_topic, other)
- description (text, nullable)
- status (enum: pending, reviewed, dismissed)
- created_at (timestamp)
```

**Threading Implementation:**

Comments use the parent_id field for threading. To retrieve threaded comments efficiently:

```php
// Eager load with nested relationships
$comments = Comment::where('article_id', $articleId)
    ->whereNull('parent_id')
    ->with(['replies.user', 'replies.reactions', 'user', 'reactions'])
    ->latest()
    ->get();
```

### 5. Search Engine

**Components:**
- `SearchController` - Search interface
- `ArticleSearchService` - Search logic
- `SearchIndexer` - Index management
- Laravel Scout with Meilisearch driver

**Search Configuration:**

```php
// Article model searchable configuration
public function toSearchableArray(): array
{
    return [
        'id' => $this->id,
        'title' => $this->title,
        'excerpt' => $this->excerpt,
        'content' => strip_tags($this->content),
        'author_name' => $this->author->name,
        'category_name' => $this->category->name,
        'tags' => $this->tags->pluck('name')->toArray(),
        'published_at' => $this->published_at?->timestamp,
    ];
}
```

**Search Features:**
- Full-text search across title, content, excerpt
- Faceted filtering (category, author, tags, date)
- Typo tolerance
- Synonym support
- Ranking by relevance and recency
- Highlighting of matched terms

**Database Schema:**

```sql
search_logs
- id (bigint, primary key)
- user_id (bigint, nullable, foreign key -> users.id)
- query (varchar 255, indexed)
- filters (json, nullable)
- results_count (int)
- clicked_result_id (bigint, nullable)
- created_at (timestamp, indexed)
```


### 6. Newsletter System

**Components:**
- `NewsletterController` - Subscription management
- `NewsletterService` - Newsletter generation
- `SendNewsletterJob` - Async email sending
- `NewsletterTemplateBuilder` - Email template generation

**Newsletter Generation Flow:**

```
1. Scheduled command runs (daily/weekly/monthly)
2. NewsletterGeneratorService queries top articles by engagement
3. NewsletterTemplateBuilder creates HTML email
4. Subscribers fetched based on frequency preference
5. SendNewsletterJob dispatched for each subscriber batch
6. Email sent via Laravel Mail with tracking pixels
7. Opens and clicks tracked via unique URLs
```

**Database Schema:**

```sql
newsletter_subscribers
- id (bigint, primary key)
- email (varchar 255, unique, indexed)
- user_id (bigint, nullable, foreign key -> users.id)
- frequency (enum: daily, weekly, monthly)
- status (enum: pending, active, unsubscribed)
- subscribed_at (timestamp, nullable)
- unsubscribed_at (timestamp, nullable)
- verification_token (varchar 100, nullable)
- created_at (timestamp)
- updated_at (timestamp)

newsletters
- id (bigint, primary key)
- subject (varchar 255)
- content (longtext)
- sent_at (timestamp, nullable)
- recipient_count (int, default 0)
- open_count (int, default 0)
- click_count (int, default 0)
- created_at (timestamp)
- updated_at (timestamp)

newsletter_sends
- id (bigint, primary key)
- newsletter_id (bigint, foreign key -> newsletters.id)
- subscriber_id (bigint, foreign key -> newsletter_subscribers.id)
- sent_at (timestamp)
- opened_at (timestamp, nullable)
- clicked_at (timestamp, nullable)
- tracking_token (varchar 100, unique)
```

### 7. Analytics Dashboard

**Components:**
- `AnalyticsController` - Dashboard interface
- `AnalyticsService` - Metrics calculation
- `ArticleViewTracker` - View tracking middleware
- `EngagementCalculator` - Engagement scoring

**Metrics Tracked:**

1. **Article Metrics:**
   - Page views (unique and total)
   - Average reading time
   - Scroll depth percentage
   - Bounce rate
   - Social shares
   - Comments count
   - Bookmark count

2. **User Metrics:**
   - Daily/Monthly active users
   - New registrations
   - User retention rate
   - Average session duration

3. **Traffic Metrics:**
   - Traffic sources (direct, search, social, referral)
   - Geographic distribution
   - Device breakdown (desktop, mobile, tablet)
   - Browser and OS statistics

**Database Schema:**

```sql
article_views
- id (bigint, primary key)
- article_id (bigint, foreign key -> articles.id, indexed)
- user_id (bigint, nullable, foreign key -> users.id)
- session_id (varchar 100, indexed)
- ip_address (varchar 45)
- user_agent (text)
- referrer (varchar 255, nullable)
- reading_time (int) // seconds
- scroll_depth (int) // percentage
- viewed_at (timestamp, indexed)

traffic_sources
- id (bigint, primary key)
- article_id (bigint, foreign key -> articles.id, indexed)
- source_type (enum: direct, search, social, referral)
- source_name (varchar 100, nullable) // google, twitter, etc.
- utm_source (varchar 100, nullable)
- utm_medium (varchar 100, nullable)
- utm_campaign (varchar 100, nullable)
- visit_count (int, default 1)
- date (date, indexed)
- unique (article_id, source_type, source_name, date)
```

**Caching Strategy:**

Analytics data is expensive to calculate in real-time. Implementation:

```php
// Cache daily metrics for 1 hour
$dailyViews = Cache::remember(
    "analytics:daily_views:{$date}",
    3600,
    fn() => ArticleView::whereDate('viewed_at', $date)->count()
);

// Cache article metrics for 15 minutes
$articleMetrics = Cache::remember(
    "analytics:article:{$articleId}",
    900,
    fn() => $this->calculateArticleMetrics($articleId)
);
```


### 8. RESTful API Layer

**Components:**
- `Api\V1\ArticleController` - Article endpoints
- `Api\V1\UserController` - User endpoints
- `Api\V1\CommentController` - Comment endpoints
- API Resources for data transformation
- API rate limiting middleware

**API Structure:**

```
/api/v1/
├── articles
│   ├── GET    /              (list articles)
│   ├── GET    /{id}          (show article)
│   ├── POST   /              (create article - auth required)
│   ├── PUT    /{id}          (update article - auth required)
│   └── DELETE /{id}          (delete article - auth required)
├── categories
│   ├── GET    /              (list categories)
│   └── GET    /{id}/articles (articles by category)
├── comments
│   ├── GET    /articles/{id}/comments (list comments)
│   ├── POST   /articles/{id}/comments (create comment - auth required)
│   └── DELETE /{id}          (delete comment - auth required)
├── users
│   ├── GET    /me            (current user - auth required)
│   ├── PUT    /me            (update profile - auth required)
│   └── GET    /{id}          (public profile)
├── bookmarks
│   ├── GET    /              (list bookmarks - auth required)
│   ├── POST   /              (create bookmark - auth required)
│   └── DELETE /{id}          (remove bookmark - auth required)
└── search
    └── GET    /              (search articles)
```

**Authentication:**

API uses Laravel Sanctum with token-based authentication:

```php
// Token generation
$token = $user->createToken('api-token')->plainTextToken;

// Request header
Authorization: Bearer {token}
```

**Rate Limiting:**

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->throttleApi('60,1'); // 60 requests per minute
    
    // Custom rate limits for specific routes
    RateLimiter::for('api-strict', function (Request $request) {
        return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
    });
})
```

**API Resources:**

```php
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when($request->routeIs('api.articles.show'), $this->content),
            'featured_image' => $this->featured_image,
            'author' => new UserResource($this->whenLoaded('author')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'published_at' => $this->published_at?->toIso8601String(),
            'reading_time' => $this->reading_time,
            'view_count' => $this->view_count,
        ];
    }
}
```

### 9. Bookmarking System

**Components:**
- `BookmarkController` - Bookmark management
- `ReadingListController` - Reading list management
- `BookmarkService` - Business logic

**Database Schema:**

```sql
bookmarks
- id (bigint, primary key)
- user_id (bigint, foreign key -> users.id, indexed)
- article_id (bigint, foreign key -> articles.id, indexed)
- reading_list_id (bigint, nullable, foreign key -> reading_lists.id)
- is_read (boolean, default false)
- read_at (timestamp, nullable)
- notes (text, nullable)
- created_at (timestamp)
- unique (user_id, article_id)

reading_lists
- id (bigint, primary key)
- user_id (bigint, foreign key -> users.id, indexed)
- name (varchar 100)
- description (text, nullable)
- visibility (enum: public, unlisted, private)
- share_token (varchar 100, unique, nullable)
- order (int, default 0)
- created_at (timestamp)
- updated_at (timestamp)

reading_list_items
- id (bigint, primary key)
- reading_list_id (bigint, foreign key -> reading_lists.id)
- article_id (bigint, foreign key -> articles.id)
- order (int, default 0)
- added_at (timestamp)
- unique (reading_list_id, article_id)
```

**Features:**
- Quick bookmark toggle on articles
- Organize bookmarks into multiple reading lists
- Mark articles as read/unread
- Add personal notes to bookmarks
- Share reading lists via unique URLs
- Drag-and-drop reordering


### 10. Social Features

**Components:**
- `SocialShareController` - Share tracking
- `FollowController` - User following
- `ActivityFeedService` - Activity aggregation

**Database Schema:**

```sql
social_shares
- id (bigint, primary key)
- article_id (bigint, foreign key -> articles.id, indexed)
- user_id (bigint, nullable, foreign key -> users.id)
- platform (enum: twitter, facebook, linkedin, reddit, hackernews)
- shared_at (timestamp, indexed)

follows
- id (bigint, primary key)
- follower_id (bigint, foreign key -> users.id, indexed)
- following_id (bigint, foreign key -> users.id, indexed)
- created_at (timestamp)
- unique (follower_id, following_id)

activities
- id (bigint, primary key)
- user_id (bigint, foreign key -> users.id, indexed)
- activity_type (enum: published_article, commented, bookmarked, followed)
- subject_type (varchar 255) // Article, Comment, User
- subject_id (bigint)
- created_at (timestamp, indexed)
```

**Open Graph Meta Tags:**

```php
// Blade template
<meta property="og:title" content="{{ $article->title }}" />
<meta property="og:description" content="{{ $article->excerpt }}" />
<meta property="og:image" content="{{ $article->featured_image }}" />
<meta property="og:url" content="{{ route('articles.show', $article->slug) }}" />
<meta property="og:type" content="article" />
<meta property="article:published_time" content="{{ $article->published_at->toIso8601String() }}" />
<meta property="article:author" content="{{ $article->author->name }}" />
```

### 11. Recommendation Engine

**Components:**
- `RecommendationService` - Recommendation logic
- `CollaborativeFilteringEngine` - User-based recommendations
- `ContentBasedEngine` - Content similarity recommendations
- `UpdateRecommendationsJob` - Async recommendation updates

**Recommendation Strategies:**

1. **Content-Based Filtering:**
   - Analyze article tags, category, and content
   - Calculate similarity scores using TF-IDF
   - Recommend articles with high similarity scores

2. **Collaborative Filtering:**
   - Find users with similar reading patterns
   - Recommend articles those users engaged with
   - Use cosine similarity for user comparison

3. **Hybrid Approach:**
   - Combine content-based and collaborative scores
   - Weight by recency and popularity
   - Personalize based on user preferences

**Database Schema:**

```sql
article_similarities
- article_id (bigint, foreign key -> articles.id, indexed)
- similar_article_id (bigint, foreign key -> articles.id)
- similarity_score (decimal 5,4)
- primary key (article_id, similar_article_id)

user_reading_history
- id (bigint, primary key)
- user_id (bigint, foreign key -> users.id, indexed)
- article_id (bigint, foreign key -> articles.id, indexed)
- engagement_score (decimal 5,4) // calculated from time, scroll, actions
- read_at (timestamp, indexed)

recommendations
- id (bigint, primary key)
- user_id (bigint, foreign key -> users.id, indexed)
- article_id (bigint, foreign key -> articles.id)
- score (decimal 5,4)
- reason (enum: similar_content, popular, trending, collaborative)
- generated_at (timestamp)
- clicked (boolean, default false)
- unique (user_id, article_id)
```

**Recommendation Algorithm:**

```php
public function generateRecommendations(User $user, int $limit = 10): Collection
{
    // Get user's reading history
    $readArticles = $user->readingHistory()->pluck('article_id');
    
    // Content-based: similar to recently read articles
    $contentBased = $this->getContentBasedRecommendations($readArticles, $limit);
    
    // Collaborative: what similar users read
    $collaborative = $this->getCollaborativeRecommendations($user, $limit);
    
    // Trending: popular recent articles
    $trending = $this->getTrendingArticles($limit);
    
    // Combine and score
    return $this->combineAndRank([
        'content' => $contentBased,
        'collaborative' => $collaborative,
        'trending' => $trending,
    ], $limit);
}
```


### 12. Notification System

**Components:**
- `NotificationController` - Notification management
- `NotificationService` - Notification creation
- Custom notification classes for each type
- Real-time notifications via Laravel Echo (optional)

**Database Schema:**

```sql
notifications (Laravel's built-in table)
- id (uuid, primary key)
- type (varchar 255)
- notifiable_type (varchar 255)
- notifiable_id (bigint, indexed)
- data (json)
- read_at (timestamp, nullable)
- created_at (timestamp, indexed)

notification_preferences
- user_id (bigint, primary key, foreign key -> users.id)
- comment_reply_email (boolean, default true)
- comment_reply_app (boolean, default true)
- new_follower_email (boolean, default true)
- new_follower_app (boolean, default true)
- author_new_article_email (boolean, default true)
- author_new_article_app (boolean, default true)
- newsletter_email (boolean, default true)
- created_at (timestamp)
- updated_at (timestamp)
```

**Notification Types:**

```php
// Comment reply notification
class CommentReplyNotification extends Notification
{
    public function via($notifiable): array
    {
        $channels = [];
        
        if ($notifiable->notificationPreferences->comment_reply_app) {
            $channels[] = 'database';
        }
        
        if ($notifiable->notificationPreferences->comment_reply_email) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }
    
    public function toArray($notifiable): array
    {
        return [
            'type' => 'comment_reply',
            'comment_id' => $this->comment->id,
            'article_id' => $this->comment->article_id,
            'article_title' => $this->comment->article->title,
            'author_name' => $this->comment->user->name,
            'author_avatar' => $this->comment->user->avatar,
            'excerpt' => Str::limit($this->comment->content, 100),
        ];
    }
}
```

**Notification Grouping:**

To prevent notification fatigue, similar notifications are grouped:

```php
// Instead of "John commented", "Jane commented", "Bob commented"
// Show "John, Jane, and Bob commented on your article"

public function groupNotifications(Collection $notifications): Collection
{
    return $notifications->groupBy(function ($notification) {
        return $notification->data['article_id'] . '_' . $notification->type;
    })->map(function ($group) {
        return $this->mergeNotifications($group);
    });
}
```

### 13. Content Moderation

**Components:**
- `ModerationController` - Moderation interface
- `ModerationService` - Moderation logic
- `AutoModerationService` - Automated flagging
- `ModerationQueueJob` - Async processing

**Moderation Features:**

1. **Automated Flagging:**
   - Keyword matching for prohibited content
   - Spam detection using pattern matching
   - Link spam detection
   - Rate limiting for new users

2. **Manual Review:**
   - Moderation queue interface
   - Bulk actions (approve, reject, delete)
   - User history and reputation
   - Moderator notes and actions log

3. **User Reputation:**
   - Track user behavior over time
   - Auto-approve trusted users
   - Increase scrutiny for flagged users

**Database Schema:**

```sql
moderation_queue
- id (bigint, primary key)
- moderatable_type (varchar 255) // Comment, Article
- moderatable_id (bigint)
- user_id (bigint, foreign key -> users.id)
- reason (enum: auto_flag, user_report, manual_review)
- flag_reasons (json) // array of specific flags
- priority (enum: low, medium, high)
- status (enum: pending, approved, rejected)
- moderator_id (bigint, nullable, foreign key -> users.id)
- moderator_notes (text, nullable)
- created_at (timestamp, indexed)
- updated_at (timestamp)

user_reputation
- user_id (bigint, primary key, foreign key -> users.id)
- reputation_score (int, default 0)
- approved_comments (int, default 0)
- rejected_comments (int, default 0)
- spam_reports (int, default 0)
- helpful_flags (int, default 0)
- trust_level (enum: new, basic, trusted, moderator)
- updated_at (timestamp)

moderation_actions
- id (bigint, primary key)
- moderator_id (bigint, foreign key -> users.id)
- action_type (enum: approve, reject, delete, ban_user)
- subject_type (varchar 255)
- subject_id (bigint)
- reason (text, nullable)
- created_at (timestamp, indexed)
```

**Auto-Moderation Rules:**

```php
class AutoModerationService
{
    protected array $prohibitedWords = [
        // List of prohibited words/phrases
    ];
    
    protected array $spamPatterns = [
        '/\b(buy|cheap|discount|click here)\b/i',
        '/https?:\/\/[^\s]+/i', // Multiple links
    ];
    
    public function shouldFlag(string $content, User $user): array
    {
        $flags = [];
        
        // Check prohibited words
        foreach ($this->prohibitedWords as $word) {
            if (stripos($content, $word) !== false) {
                $flags[] = 'prohibited_content';
                break;
            }
        }
        
        // Check spam patterns
        foreach ($this->spamPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $flags[] = 'potential_spam';
                break;
            }
        }
        
        // Check user reputation
        if ($user->reputation->trust_level === 'new' && $user->reputation->spam_reports > 0) {
            $flags[] = 'low_reputation';
        }
        
        return $flags;
    }
}
```


## Data Models

### Core Models and Relationships

```php
// Article Model
class Article extends Model
{
    use HasFactory, SoftDeletes, Searchable;
    
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'status' => ArticleStatus::class,
        ];
    }
    
    // Relationships
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
    
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
    
    public function views(): HasMany
    {
        return $this->hasMany(ArticleView::class);
    }
    
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }
    
    // Scopes
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published)
              ->whereNotNull('published_at')
              ->where('published_at', '<=', now());
    }
    
    public function scopePopular(Builder $query, int $days = 7): void
    {
        $query->withCount(['views' => function ($q) use ($days) {
            $q->where('viewed_at', '>=', now()->subDays($days));
        }])->orderByDesc('views_count');
    }
    
    // Accessors
    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return (int) ceil($wordCount / 200); // 200 words per minute
    }
}

// User Model
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
    
    // Relationships
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }
    
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
    
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }
    
    public function readingLists(): HasMany
    {
        return $this->hasMany(ReadingList::class);
    }
    
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
                    ->withTimestamps();
    }
    
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
                    ->withTimestamps();
    }
    
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }
    
    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreferences::class);
    }
    
    public function reputation(): HasOne
    {
        return $this->hasOne(UserReputation::class);
    }
    
    // Helper methods
    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }
    
    public function hasBookmarked(Article $article): bool
    {
        return $this->bookmarks()->where('article_id', $article->id)->exists();
    }
}

// Comment Model
class Comment extends Model
{
    use HasFactory, SoftDeletes;
    
    protected function casts(): array
    {
        return [
            'status' => CommentStatus::class,
            'moderated_at' => 'datetime',
        ];
    }
    
    // Relationships
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }
    
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
    
    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }
    
    // Scopes
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', CommentStatus::Approved);
    }
    
    public function scopeTopLevel(Builder $query): void
    {
        $query->whereNull('parent_id');
    }
}

// Category Model
class Category extends Model
{
    use HasFactory;
    
    // Relationships
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
    
    // Scopes
    public function scopeRoot(Builder $query): void
    {
        $query->whereNull('parent_id');
    }
}
```

### Enums

```php
enum ArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}

enum UserRole: string
{
    case Reader = 'reader';
    case Author = 'author';
    case Moderator = 'moderator';
    case Admin = 'admin';
    
    public function can(string $permission): bool
    {
        return match($this) {
            self::Admin => true,
            self::Moderator => in_array($permission, ['moderate', 'edit_comments']),
            self::Author => in_array($permission, ['create_article', 'edit_own_article']),
            self::Reader => in_array($permission, ['comment', 'bookmark']),
        };
    }
}

enum CommentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Flagged = 'flagged';
}
```


## Error Handling

### Exception Handling Strategy

**Custom Exceptions:**

```php
// app/Exceptions/ArticleNotFoundException.php
class ArticleNotFoundException extends Exception
{
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Article not found',
                'message' => 'The requested article does not exist or has been removed.'
            ], 404);
        }
        
        return response()->view('errors.article-not-found', [], 404);
    }
}

// app/Exceptions/UnauthorizedActionException.php
class UnauthorizedActionException extends Exception
{
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You do not have permission to perform this action.'
            ], 403);
        }
        
        return redirect()->back()->with('error', 'You do not have permission to perform this action.');
    }
}

// app/Exceptions/RateLimitExceededException.php
class RateLimitExceededException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => 60
        ], 429);
    }
}
```

**Global Exception Handler:**

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    // Log all exceptions
    $exceptions->report(function (Throwable $e) {
        if (app()->bound('sentry')) {
            app('sentry')->captureException($e);
        }
    });
    
    // Custom rendering
    $exceptions->render(function (ModelNotFoundException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Resource not found',
                'message' => 'The requested resource does not exist.'
            ], 404);
        }
    });
    
    $exceptions->render(function (ValidationException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'The provided data is invalid.',
                'errors' => $e->errors()
            ], 422);
        }
    });
})
```

### Validation

**Form Request Classes:**

```php
// app/Http/Requests/StoreArticleRequest.php
class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->can('create_article');
    }
    
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:articles,slug'],
            'excerpt' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string', 'min:100'],
            'category_id' => ['required', 'exists:categories,id'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'featured_image' => ['nullable', 'image', 'max:2048'],
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'published_at' => ['nullable', 'date', 'after_or_equal:now'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for your article.',
            'content.min' => 'Article content must be at least 100 characters.',
            'category_id.exists' => 'The selected category is invalid.',
            'featured_image.max' => 'Featured image must not exceed 2MB.',
        ];
    }
    
    protected function prepareForValidation(): void
    {
        if (!$this->has('slug') && $this->has('title')) {
            $this->merge([
                'slug' => Str::slug($this->title)
            ]);
        }
    }
}

// app/Http/Requests/StoreCommentRequest.php
class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }
    
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:10', 'max:5000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'content.required' => 'Comment content is required.',
            'content.min' => 'Comment must be at least 10 characters.',
            'content.max' => 'Comment must not exceed 5000 characters.',
        ];
    }
}
```

### Error Logging

**Logging Configuration:**

```php
// config/logging.php - Custom channels
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
        'ignore_exceptions' => false,
    ],
    
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
    
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],
    
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90,
    ],
];
```

**Contextual Logging:**

```php
// Log security events
Log::channel('security')->warning('Failed login attempt', [
    'email' => $request->email,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);

// Log business events
Log::info('Article published', [
    'article_id' => $article->id,
    'author_id' => $article->author_id,
    'category' => $article->category->name,
]);

// Log errors with context
try {
    $this->processPayment($order);
} catch (PaymentException $e) {
    Log::error('Payment processing failed', [
        'order_id' => $order->id,
        'amount' => $order->total,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    throw $e;
}
```


## Testing Strategy

### Testing Pyramid

```
                    /\
                   /  \
                  / E2E \          (Few - Critical user flows)
                 /______\
                /        \
               /  Feature \        (Many - API endpoints, user actions)
              /____________\
             /              \
            /   Unit Tests   \    (Most - Models, services, helpers)
           /__________________\
```

### Unit Tests

**Model Tests:**

```php
// tests/Unit/Models/ArticleTest.php
class ArticleTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_article_belongs_to_author(): void
    {
        $article = Article::factory()->create();
        
        $this->assertInstanceOf(User::class, $article->author);
    }
    
    public function test_article_calculates_reading_time(): void
    {
        $article = Article::factory()->create([
            'content' => str_repeat('word ', 400) // 400 words
        ]);
        
        $this->assertEquals(2, $article->reading_time); // 400 words / 200 wpm = 2 minutes
    }
    
    public function test_published_scope_only_returns_published_articles(): void
    {
        Article::factory()->count(3)->create(['status' => ArticleStatus::Published]);
        Article::factory()->count(2)->create(['status' => ArticleStatus::Draft]);
        
        $publishedCount = Article::published()->count();
        
        $this->assertEquals(3, $publishedCount);
    }
}

// tests/Unit/Services/RecommendationServiceTest.php
class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_generates_content_based_recommendations(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        
        // User reads articles in a specific category
        $readArticles = Article::factory()->count(3)->create(['category_id' => $category->id]);
        foreach ($readArticles as $article) {
            UserReadingHistory::create([
                'user_id' => $user->id,
                'article_id' => $article->id,
                'engagement_score' => 0.8,
            ]);
        }
        
        // Create similar articles
        $similarArticles = Article::factory()->count(5)->create(['category_id' => $category->id]);
        
        $service = new RecommendationService();
        $recommendations = $service->generateRecommendations($user, 5);
        
        $this->assertCount(5, $recommendations);
        $this->assertTrue($recommendations->pluck('category_id')->every(fn($id) => $id === $category->id));
    }
}
```

### Feature Tests

**Article Management Tests:**

```php
// tests/Feature/ArticleManagementTest.php
class ArticleManagementTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_author_can_create_article(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);
        $category = Category::factory()->create();
        
        $response = $this->actingAs($author)->post(route('articles.store'), [
            'title' => 'Test Article',
            'excerpt' => 'This is a test excerpt',
            'content' => str_repeat('Test content. ', 50),
            'category_id' => $category->id,
            'status' => ArticleStatus::Published->value,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('articles', [
            'title' => 'Test Article',
            'author_id' => $author->id,
        ]);
    }
    
    public function test_reader_cannot_create_article(): void
    {
        $reader = User::factory()->create(['role' => UserRole::Reader]);
        $category = Category::factory()->create();
        
        $response = $this->actingAs($reader)->post(route('articles.store'), [
            'title' => 'Test Article',
            'excerpt' => 'This is a test excerpt',
            'content' => str_repeat('Test content. ', 50),
            'category_id' => $category->id,
        ]);
        
        $response->assertForbidden();
    }
    
    public function test_article_view_is_tracked(): void
    {
        $article = Article::factory()->create(['status' => ArticleStatus::Published]);
        
        $response = $this->get(route('articles.show', $article->slug));
        
        $response->assertOk();
        $this->assertDatabaseHas('article_views', [
            'article_id' => $article->id,
        ]);
    }
}

// tests/Feature/CommentSystemTest.php
class CommentSystemTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_authenticated_user_can_comment(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['status' => ArticleStatus::Published]);
        
        $response = $this->actingAs($user)->post(route('comments.store', $article), [
            'content' => 'This is a great article!',
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'article_id' => $article->id,
            'user_id' => $user->id,
            'content' => 'This is a great article!',
        ]);
    }
    
    public function test_comment_with_prohibited_words_is_flagged(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['status' => ArticleStatus::Published]);
        
        $response = $this->actingAs($user)->post(route('comments.store', $article), [
            'content' => 'Buy cheap products at example.com',
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'article_id' => $article->id,
            'status' => CommentStatus::Flagged->value,
        ]);
    }
    
    public function test_user_can_reply_to_comment(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['status' => ArticleStatus::Published]);
        $parentComment = Comment::factory()->create([
            'article_id' => $article->id,
            'status' => CommentStatus::Approved,
        ]);
        
        $response = $this->actingAs($user)->post(route('comments.store', $article), [
            'content' => 'I agree with this comment!',
            'parent_id' => $parentComment->id,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'article_id' => $article->id,
            'parent_id' => $parentComment->id,
            'content' => 'I agree with this comment!',
        ]);
    }
}
```

### API Tests

```php
// tests/Feature/Api/ArticleApiTest.php
class ArticleApiTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_list_articles(): void
    {
        Article::factory()->count(15)->create(['status' => ArticleStatus::Published]);
        
        $response = $this->getJson('/api/v1/articles');
        
        $response->assertOk()
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'title', 'slug', 'excerpt', 'author', 'category', 'published_at']
                     ],
                     'meta' => ['current_page', 'total', 'per_page'],
                     'links' => ['first', 'last', 'prev', 'next']
                 ]);
    }
    
    public function test_can_create_article_with_valid_token(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);
        $token = $author->createToken('test-token')->plainTextToken;
        $category = Category::factory()->create();
        
        $response = $this->withToken($token)->postJson('/api/v1/articles', [
            'title' => 'API Test Article',
            'excerpt' => 'Test excerpt',
            'content' => str_repeat('Test content. ', 50),
            'category_id' => $category->id,
            'status' => ArticleStatus::Published->value,
        ]);
        
        $response->assertCreated()
                 ->assertJsonStructure(['data' => ['id', 'title', 'slug']]);
    }
    
    public function test_rate_limiting_works(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        // Make 61 requests (rate limit is 60 per minute)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->withToken($token)->getJson('/api/v1/articles');
        }
        
        $response->assertStatus(429); // Too Many Requests
    }
}
```

### Performance Tests

```php
// tests/Performance/ArticleLoadTest.php
class ArticleLoadTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_article_page_loads_within_acceptable_time(): void
    {
        $article = Article::factory()
            ->has(Comment::factory()->count(50))
            ->create(['status' => ArticleStatus::Published]);
        
        $startTime = microtime(true);
        
        $response = $this->get(route('articles.show', $article->slug));
        
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertOk();
        $this->assertLessThan(500, $loadTime, "Page load time exceeded 500ms: {$loadTime}ms");
    }
    
    public function test_search_performs_efficiently(): void
    {
        Article::factory()->count(1000)->create(['status' => ArticleStatus::Published]);
        
        $startTime = microtime(true);
        
        $response = $this->get(route('search', ['q' => 'test']));
        
        $endTime = microtime(true);
        $searchTime = ($endTime - $startTime) * 1000;
        
        $response->assertOk();
        $this->assertLessThan(1000, $searchTime, "Search time exceeded 1000ms: {$searchTime}ms");
    }
}
```

### Test Coverage Goals

- **Unit Tests**: 80%+ coverage for models, services, and helpers
- **Feature Tests**: 100% coverage for critical user flows
- **API Tests**: 100% coverage for all API endpoints
- **Performance Tests**: Key pages and operations

### Continuous Integration

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
      
      redis:
        image: redis:7
        ports:
          - 6379:6379
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
          extensions: mbstring, pdo_mysql, redis
      
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Run tests
        run: php artisan test --parallel --coverage
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password
```


## Performance Optimization

### Caching Strategy

**Multi-Layer Caching:**

```php
// 1. Application Cache (Redis)
Cache::remember('popular_articles', 3600, function () {
    return Article::published()
        ->popular(7)
        ->limit(10)
        ->get();
});

// 2. Query Result Cache
$articles = Article::published()
    ->with(['author', 'category', 'tags'])
    ->remember(900) // Cache for 15 minutes
    ->paginate(15);

// 3. Model Cache
class Article extends Model
{
    protected static function booted(): void
    {
        static::updated(function ($article) {
            Cache::forget("article:{$article->id}");
            Cache::forget("article:slug:{$article->slug}");
        });
    }
    
    public static function findCached(int $id): ?self
    {
        return Cache::remember(
            "article:{$id}",
            3600,
            fn() => self::with(['author', 'category', 'tags'])->find($id)
        );
    }
}

// 4. Fragment Caching in Blade
@cache('sidebar.popular', 3600)
    <div class="popular-articles">
        @foreach($popularArticles as $article)
            <x-article-card :article="$article" />
        @endforeach
    </div>
@endcache

// 5. HTTP Cache Headers
Route::get('/articles/{article:slug}', function (Article $article) {
    return response()
        ->view('articles.show', compact('article'))
        ->header('Cache-Control', 'public, max-age=3600')
        ->header('ETag', md5($article->updated_at));
});
```

**Cache Invalidation Strategy:**

```php
// Event-based cache invalidation
class ArticlePublished
{
    public function __construct(public Article $article) {}
}

class InvalidateArticleCache
{
    public function handle(ArticlePublished $event): void
    {
        $article = $event->article;
        
        // Invalidate specific article caches
        Cache::forget("article:{$article->id}");
        Cache::forget("article:slug:{$article->slug}");
        
        // Invalidate list caches
        Cache::forget('popular_articles');
        Cache::forget("category:{$article->category_id}:articles");
        Cache::forget('homepage_articles');
        
        // Invalidate author cache
        Cache::forget("author:{$article->author_id}:articles");
    }
}
```

### Database Optimization

**Indexing Strategy:**

```php
// Migration with proper indexes
Schema::create('articles', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('excerpt');
    $table->longText('content');
    $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('category_id')->constrained()->onDelete('restrict');
    $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
    $table->timestamp('published_at')->nullable();
    $table->integer('reading_time');
    $table->bigInteger('view_count')->default(0);
    $table->timestamps();
    $table->softDeletes();
    
    // Composite indexes for common queries
    $table->index(['status', 'published_at']); // For published articles list
    $table->index(['author_id', 'status']); // For author's articles
    $table->index(['category_id', 'published_at']); // For category pages
    $table->index('view_count'); // For popular articles
});

// Full-text search index
Schema::table('articles', function (Blueprint $table) {
    $table->fullText(['title', 'excerpt', 'content']);
});
```

**Query Optimization:**

```php
// Bad: N+1 query problem
$articles = Article::all();
foreach ($articles as $article) {
    echo $article->author->name; // Triggers a query for each article
}

// Good: Eager loading
$articles = Article::with('author')->get();
foreach ($articles as $article) {
    echo $article->author->name; // No additional queries
}

// Better: Eager loading with constraints
$articles = Article::with([
    'author:id,name,avatar',
    'category:id,name,slug',
    'tags:id,name',
    'comments' => fn($q) => $q->approved()->limit(5)
])->published()->paginate(15);

// Best: Selective loading based on need
$articles = Article::select(['id', 'title', 'slug', 'excerpt', 'author_id', 'published_at'])
    ->with('author:id,name')
    ->published()
    ->paginate(15);
```

**Database Connection Pooling:**

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => 'InnoDB',
    'options' => [
        PDO::ATTR_PERSISTENT => true, // Connection pooling
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
],
```

### Asset Optimization

**Vite Configuration:**

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['alpinejs'],
                    'editor': ['@tiptap/core', '@tiptap/starter-kit'],
                },
            },
        },
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
            },
        },
    },
});
```

**Image Optimization:**

```php
// Service for image processing
class ImageOptimizationService
{
    public function optimize(UploadedFile $file): string
    {
        $image = Image::make($file);
        
        // Resize if too large
        if ($image->width() > 1920) {
            $image->resize(1920, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        
        // Optimize quality
        $image->encode('webp', 85);
        
        // Generate filename
        $filename = Str::random(40) . '.webp';
        $path = "images/{$filename}";
        
        // Store to S3 with CDN
        Storage::disk('s3')->put($path, $image->stream());
        
        return Storage::disk('s3')->url($path);
    }
    
    public function generateResponsiveImages(string $path): array
    {
        $sizes = [320, 640, 768, 1024, 1920];
        $variants = [];
        
        foreach ($sizes as $size) {
            $image = Image::make(Storage::disk('s3')->get($path));
            $image->resize($size, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            
            $variantPath = str_replace('.webp', "-{$size}w.webp", $path);
            Storage::disk('s3')->put($variantPath, $image->encode('webp', 85));
            $variants[$size] = Storage::disk('s3')->url($variantPath);
        }
        
        return $variants;
    }
}
```

### Queue Optimization

**Job Batching:**

```php
// Batch newsletter sending
$subscribers = NewsletterSubscriber::active()->get();

$batch = Bus::batch(
    $subscribers->chunk(100)->map(function ($chunk) use ($newsletter) {
        return new SendNewsletterBatch($newsletter, $chunk);
    })
)->then(function (Batch $batch) {
    Log::info("Newsletter sent to {$batch->totalJobs} batches");
})->catch(function (Batch $batch, Throwable $e) {
    Log::error("Newsletter batch failed: {$e->getMessage()}");
})->finally(function (Batch $batch) {
    Cache::forget('newsletter_sending');
})->dispatch();
```

**Queue Prioritization:**

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],

// Dispatch to different queues based on priority
SendEmailNotification::dispatch($user, $notification)
    ->onQueue('high'); // Critical notifications

ProcessAnalytics::dispatch($data)
    ->onQueue('low'); // Background processing

// Worker configuration
php artisan queue:work --queue=high,default,low
```

### CDN Integration

```php
// config/filesystems.php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    ],
    
    'cloudfront' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('CLOUDFRONT_URL'), // CloudFront distribution URL
    ],
],

// Helper for CDN URLs
function cdn_asset(string $path): string
{
    if (app()->environment('production')) {
        return Storage::disk('cloudfront')->url($path);
    }
    
    return Storage::disk('public')->url($path);
}
```


## Security Implementation

### Authentication Security

**Password Hashing:**

```php
// Laravel automatically uses bcrypt with cost factor 12
// config/hashing.php
'bcrypt' => [
    'rounds' => env('BCRYPT_ROUNDS', 12),
],

// Custom password validation rules
class PasswordValidationRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        return strlen($value) >= 8
            && preg_match('/[a-z]/', $value)
            && preg_match('/[A-Z]/', $value)
            && preg_match('/[0-9]/', $value)
            && preg_match('/[@$!%*#?&]/', $value);
    }
    
    public function message(): string
    {
        return 'Password must be at least 8 characters and contain uppercase, lowercase, number, and special character.';
    }
}
```

**Session Security:**

```php
// config/session.php
'lifetime' => 120, // 2 hours
'expire_on_close' => false,
'encrypt' => true,
'http_only' => true,
'same_site' => 'lax',
'secure' => env('SESSION_SECURE_COOKIE', true), // HTTPS only in production
```

**CSRF Protection:**

```php
// Enabled by default in Laravel
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'api/*', // API routes use token authentication
        'webhooks/*', // Webhook endpoints
    ]);
})
```

**Rate Limiting:**

```php
// bootstrap/app.php
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->email . $request->ip())
        ->response(function () {
            return response()->json([
                'error' => 'Too many login attempts. Please try again in 1 minute.'
            ], 429);
        });
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Apply to routes
Route::middleware('throttle:login')->post('/login', [AuthController::class, 'login']);
```

### Authorization

**Policies:**

```php
// app/Policies/ArticlePolicy.php
class ArticlePolicy
{
    public function viewAny(?User $user): bool
    {
        return true; // Anyone can view articles list
    }
    
    public function view(?User $user, Article $article): bool
    {
        // Published articles are public
        if ($article->status === ArticleStatus::Published) {
            return true;
        }
        
        // Drafts only visible to author and admins
        return $user && ($user->id === $article->author_id || $user->role === UserRole::Admin);
    }
    
    public function create(User $user): bool
    {
        return $user->role->can('create_article');
    }
    
    public function update(User $user, Article $article): bool
    {
        // Authors can edit their own articles, admins can edit any
        return $user->id === $article->author_id || $user->role === UserRole::Admin;
    }
    
    public function delete(User $user, Article $article): bool
    {
        return $user->id === $article->author_id || $user->role === UserRole::Admin;
    }
    
    public function publish(User $user, Article $article): bool
    {
        return $user->id === $article->author_id || $user->role === UserRole::Admin;
    }
}

// Usage in controllers
public function update(UpdateArticleRequest $request, Article $article)
{
    $this->authorize('update', $article);
    
    $article->update($request->validated());
    
    return redirect()->route('articles.show', $article->slug);
}
```

### Input Sanitization

**XSS Prevention:**

```php
// Blade automatically escapes output
{{ $article->title }} // Safe

// Raw output (use with caution)
{!! $article->content !!} // Must be sanitized

// HTML Purifier for user-generated content
use HTMLPurifier;
use HTMLPurifier_Config;

class ContentSanitizer
{
    public function sanitize(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,u,a[href],ul,ol,li,code,pre,blockquote,h2,h3,h4');
        $config->set('AutoFormat.RemoveEmpty', true);
        
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }
}

// Apply to user content
$comment->content = $this->sanitizer->sanitize($request->content);
```

**SQL Injection Prevention:**

```php
// Laravel's query builder and Eloquent automatically prevent SQL injection
// Always use parameter binding

// Good: Parameterized query
$articles = Article::where('category_id', $categoryId)->get();

// Good: Named bindings
$articles = DB::select('SELECT * FROM articles WHERE category_id = :category', [
    'category' => $categoryId
]);

// Bad: String concatenation (never do this)
// $articles = DB::select("SELECT * FROM articles WHERE category_id = {$categoryId}");
```

### Data Protection

**Encryption:**

```php
// Encrypt sensitive data
use Illuminate\Support\Facades\Crypt;

// Store encrypted
$user->api_secret = Crypt::encryptString($apiSecret);
$user->save();

// Retrieve decrypted
$apiSecret = Crypt::decryptString($user->api_secret);

// Model attribute encryption
class User extends Model
{
    protected function casts(): array
    {
        return [
            'api_secret' => 'encrypted',
            'social_security_number' => 'encrypted',
        ];
    }
}
```

**GDPR Compliance:**

```php
// Data export
class ExportUserDataAction
{
    public function execute(User $user): array
    {
        return [
            'profile' => $user->only(['name', 'email', 'created_at']),
            'articles' => $user->articles()->get(['title', 'published_at']),
            'comments' => $user->comments()->get(['content', 'created_at']),
            'bookmarks' => $user->bookmarks()->with('article:id,title')->get(),
            'reading_history' => $user->readingHistory()->get(),
        ];
    }
}

// Data deletion
class DeleteUserDataAction
{
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Anonymize comments instead of deleting
            $user->comments()->update([
                'user_id' => null,
                'content' => '[deleted]',
            ]);
            
            // Delete personal data
            $user->bookmarks()->delete();
            $user->readingHistory()->delete();
            $user->notifications()->delete();
            $user->profile()->delete();
            $user->preferences()->delete();
            
            // Soft delete user
            $user->delete();
        });
    }
}
```

### Security Headers

```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        // Content Security Policy
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "img-src 'self' data: https:",
            "font-src 'self' https://fonts.gstatic.com",
            "connect-src 'self'",
            "frame-ancestors 'self'",
        ]));
        
        return $response;
    }
}

// Register in bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(SecurityHeaders::class);
})
```

### API Security

**Token Management:**

```php
// Generate API token with abilities
$token = $user->createToken('mobile-app', ['read', 'write'])->plainTextToken;

// Verify token abilities in middleware
Route::middleware(['auth:sanctum', 'ability:write'])->group(function () {
    Route::post('/articles', [ArticleController::class, 'store']);
});

// Token expiration
class ExpireTokensCommand extends Command
{
    protected $signature = 'tokens:expire';
    
    public function handle(): void
    {
        PersonalAccessToken::where('created_at', '<', now()->subDays(30))
            ->delete();
        
        $this->info('Expired tokens deleted.');
    }
}
```

**API Request Validation:**

```php
// Validate API requests strictly
class ApiArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('write');
    }
    
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:100'],
            'category_id' => ['required', 'exists:categories,id'],
        ];
    }
    
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
```


## Deployment and Infrastructure

### Environment Configuration

**Production Environment Variables:**

```env
APP_NAME="Tech News Platform"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://technews.example.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=production-db.example.com
DB_PORT=3306
DB_DATABASE=technews_prod
DB_USERNAME=technews_user
DB_PASSWORD=...

BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=s3
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=production-redis.example.com
REDIS_PASSWORD=...
REDIS_PORT=6379

MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@technews.example.com
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=technews-media
CLOUDFRONT_URL=https://cdn.technews.example.com

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=...

SENTRY_LARAVEL_DSN=...
```

### Docker Configuration

**Dockerfile:**

```dockerfile
FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Optimize for production
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 9000

CMD ["php-fpm"]
```

**docker-compose.yml (Production):**

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: technews-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./storage:/var/www/storage
    networks:
      - technews-network
    depends_on:
      - mysql
      - redis
      - meilisearch

  nginx:
    image: nginx:alpine
    container_name: technews-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx:/etc/nginx/conf.d
      - ./docker/ssl:/etc/nginx/ssl
    networks:
      - technews-network
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: technews-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - technews-network
    command: --default-authentication-plugin=mysql_native_password

  redis:
    image: redis:7-alpine
    container_name: technews-redis
    restart: unless-stopped
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis-data:/data
    networks:
      - technews-network

  meilisearch:
    image: getmeili/meilisearch:latest
    container_name: technews-meilisearch
    restart: unless-stopped
    environment:
      MEILI_MASTER_KEY: ${MEILISEARCH_KEY}
    volumes:
      - meilisearch-data:/meili_data
    networks:
      - technews-network

  queue-worker:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: technews-queue
    restart: unless-stopped
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    volumes:
      - ./:/var/www
    networks:
      - technews-network
    depends_on:
      - app
      - redis

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: technews-scheduler
    restart: unless-stopped
    command: php artisan schedule:work
    volumes:
      - ./:/var/www
    networks:
      - technews-network
    depends_on:
      - app

networks:
  technews-network:
    driver: bridge

volumes:
  mysql-data:
  redis-data:
  meilisearch-data:
```

### Nginx Configuration

```nginx
# docker/nginx/default.conf
server {
    listen 80;
    listen [::]:80;
    server_name technews.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name technews.example.com;
    root /var/www/public;

    index index.php;

    # SSL Configuration
    ssl_certificate /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;

    # Client body size
    client_max_body_size 20M;

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### CI/CD Pipeline

**GitHub Actions Workflow:**

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
      
      redis:
        image: redis:7
        ports:
          - 6379:6379
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
          extensions: mbstring, pdo_mysql, redis
      
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Run tests
        run: php artisan test --parallel
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password
      
      - name: Run Pint
        run: vendor/bin/pint --test

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy to production
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.PRODUCTION_HOST }}
          username: ${{ secrets.PRODUCTION_USER }}
          key: ${{ secrets.PRODUCTION_SSH_KEY }}
          script: |
            cd /var/www/technews
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
            npm run build
```

### Monitoring and Logging

**Application Monitoring:**

```php
// config/logging.php - Production logging
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'sentry'],
    ],
    
    'sentry' => [
        'driver' => 'sentry',
        'level' => 'error',
    ],
],

// Performance monitoring
class PerformanceMonitor
{
    public function trackPageLoad(Request $request, Response $response, float $duration): void
    {
        if ($duration > 1000) { // More than 1 second
            Log::warning('Slow page load', [
                'url' => $request->fullUrl(),
                'duration' => $duration,
                'memory' => memory_get_peak_usage(true),
            ]);
        }
        
        // Send to monitoring service
        if (app()->bound('metrics')) {
            app('metrics')->timing('page.load', $duration, [
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
            ]);
        }
    }
}
```

**Health Check Endpoint:**

```php
// routes/web.php
Route::get('/health', function () {
    $checks = [
        'database' => false,
        'redis' => false,
        'storage' => false,
        'queue' => false,
    ];
    
    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Exception $e) {
        Log::error('Database health check failed', ['error' => $e->getMessage()]);
    }
    
    try {
        Redis::ping();
        $checks['redis'] = true;
    } catch (\Exception $e) {
        Log::error('Redis health check failed', ['error' => $e->getMessage()]);
    }
    
    try {
        Storage::disk('s3')->exists('health-check.txt');
        $checks['storage'] = true;
    } catch (\Exception $e) {
        Log::error('Storage health check failed', ['error' => $e->getMessage()]);
    }
    
    $checks['queue'] = Cache::has('queue:heartbeat');
    
    $healthy = !in_array(false, $checks, true);
    
    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
});
```

### Backup Strategy

```php
// app/Console/Commands/BackupDatabase.php
class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    
    public function handle(): void
    {
        $filename = 'backup-' . now()->format('Y-m-d-H-i-s') . '.sql';
        $path = storage_path("backups/{$filename}");
        
        // Create backup
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s',
            config('database.connections.mysql.host'),
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            $path
        );
        
        exec($command);
        
        // Upload to S3
        Storage::disk('s3')->put("backups/{$filename}", file_get_contents($path));
        
        // Delete local copy
        unlink($path);
        
        // Delete old backups (keep last 30 days)
        $oldBackups = Storage::disk('s3')->files('backups');
        foreach ($oldBackups as $backup) {
            $date = Carbon::createFromFormat('Y-m-d-H-i-s', 
                str_replace(['backup-', '.sql'], '', basename($backup))
            );
            
            if ($date->lt(now()->subDays(30))) {
                Storage::disk('s3')->delete($backup);
            }
        }
        
        $this->info("Database backup created: {$filename}");
    }
}

// Schedule in routes/console.php
Schedule::command('backup:database')->daily()->at('02:00');
```

