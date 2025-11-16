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

