# Design Document

## Overview

TechNewsHub is a comprehensive news and blog platform built on Laravel 12 with SQLite, designed to provide a modern content management experience for technology-focused publications. The system architecture follows Laravel's MVC pattern with a clear separation between public-facing frontend, administrative backend, and RESTful API layers.

### Core Technology Stack

- **Backend Framework**: Laravel 12 (PHP 8.4)
- **Database**: SQLite
- **Frontend**: Blade templates with Alpine.js and Tailwind CSS v3
- **Authentication**: Laravel Breeze with Sanctum for API tokens
- **Asset Bundling**: Vite
- **Testing**: PHPUnit v11

### Design Principles

1. **Security First**: All user inputs sanitized, CSRF protection, XSS prevention, rate limiting
2. **Performance Optimized**: Eager loading, query optimization, caching strategies, lazy loading
3. **Mobile-First Responsive**: Tailwind CSS with mobile-first approach
4. **Accessibility Compliant**: WCAG AA standards, semantic HTML, proper ARIA labels
5. **SEO Optimized**: Structured data, meta tags, sitemaps, Open Graph support
6. **Scalable Architecture**: Repository pattern, service layer, job queues

## Architecture

### High-Level System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Frontend Layer                          │
│  (Blade Templates + Alpine.js + Tailwind CSS)              │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                   Application Layer                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ Controllers  │  │  Middleware  │  │  Policies    │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                    Business Logic Layer                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │  Services    │  │    Jobs      │  │   Events     │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                      Data Layer                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │   Models     │  │  Migrations  │  │   Seeders    │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────┘
                            │
                     ┌──────────────┐
                     │   SQLite DB  │
                     └──────────────┘
```

### Application Structure

Following Laravel 12's streamlined structure:

```
app/
├── Console/Commands/          # Artisan commands (auto-registered)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/            # Admin panel controllers
│   │   ├── Api/              # API controllers
│   │   └── Auth/             # Authentication controllers
│   ├── Middleware/           # Custom middleware
│   ├── Requests/             # Form request validation
│   └── Resources/            # API resources
├── Models/                   # Eloquent models
├── Policies/                 # Authorization policies
├── Services/                 # Business logic services
├── Jobs/                     # Queued jobs
└── Traits/                   # Reusable traits

bootstrap/
├── app.php                   # Application bootstrap (middleware, routing)
└── providers.php             # Service providers

routes/
├── web.php                   # Web routes
├── api.php                   # API routes
└── console.php               # Console routes
```

## Components and Interfaces

### 1. Authentication & Authorization System

**Design Decision**: Use Laravel Breeze for authentication scaffolding with role-based access control.

**Components**:
- `RoleMiddleware`: Checks user roles (Admin, Editor, Author)
- `AdminMiddleware`: Restricts access to admin panel
- `PostPolicy`: Authorizes post operations based on ownership and role

**User Roles & Permissions**:

| Role   | Permissions |
|--------|-------------|
| Admin  | Full access to all features, user management, settings |
| Editor | Create/edit/publish all posts, manage comments, categories, tags |
| Author | Create/edit own posts (draft only), view own analytics |

**Session Management**:
- Session timeout: 120 minutes of inactivity
- Laravel's session middleware handles expiration
- Redirect to login page on session expiration

**Two-Factor Authentication**:
- Use `laravel/fortify` or custom implementation with Google Authenticator
- Store 2FA secrets encrypted in users table
- Generate 10 backup codes (hashed) for account recovery
- "Remember device" cookie valid for 30 days
- Account lockout after 5 failed 2FA attempts (15 minutes)

### 2. Post Management System

**Design Decision**: Rich content editor using TinyMCE or Trix with custom image handling.

**Post Model Structure**:
```php
class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image',
        'status', 'published_at', 'scheduled_at', 'reading_time',
        'view_count', 'user_id', 'category_id', 'meta_title',
        'meta_description', 'meta_keywords'
    ];
    
    protected $casts = [
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];
    
    // Relationships
    public function author(): BelongsTo;
    public function category(): BelongsTo;
    public function tags(): BelongsToMany;
    public function comments(): HasMany;
    public function revisions(): HasMany;
    public function views(): HasMany;
}
```

**Post Statuses**:
- `draft`: Not visible publicly, editable by author
- `scheduled`: Queued for future publication
- `published`: Live and visible to public
- `archived`: Hidden but preserved

**Slug Generation**:
- Use `Str::slug()` with uniqueness check
- Append numeric suffix if duplicate exists
- Update slug only on first save unless manually changed

**Reading Time Calculation**:
```php
class PostService
{
    public function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        return (int) ceil($wordCount / 200); // 200 words per minute
    }
}
```

**Content Scheduling**:
- Scheduled posts checked via Laravel scheduler (every minute)
- `PublishScheduledPostsCommand` finds posts where `scheduled_at <= now()`
- Update status to `published`, set `published_at`, send notification email
- Use queued job for email notifications to prevent blocking

### 3. Category & Tag Organization

**Design Decision**: Hierarchical categories with nested set model or adjacency list, flat tags with pivot table.

**Category Model**:
```php
class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'parent_id', 'order'];
    
    public function parent(): BelongsTo;
    public function children(): HasMany;
    public function posts(): HasMany;
    
    // Prevent deletion if posts exist
    protected static function booted()
    {
        static::deleting(function ($category) {
            if ($category->posts()->count() > 0) {
                throw new \Exception('Cannot delete category with posts');
            }
        });
    }
}
```

**Tag Model**:
```php
class Tag extends Model
{
    protected $fillable = ['name', 'slug', 'description'];
    
    public function posts(): BelongsToMany;
}
```

**Slug Auto-generation**:
- Model observer generates slug on create/update
- Ensures uniqueness within model scope

### 4. Media Library Management

**Design Decision**: Store files in Laravel storage with database metadata tracking.

**Media Model**:
```php
class Media extends Model
{
    protected $fillable = [
        'filename', 'original_filename', 'mime_type', 'size',
        'path', 'disk', 'user_id', 'alt_text', 'caption'
    ];
    
    protected $casts = [
        'metadata' => 'array', // Store image dimensions, variants
    ];
}
```

**File Upload Flow**:
1. Validate file type and size (max 10MB for images)
2. Generate unique filename with timestamp
3. Store original file in `storage/app/public/media`
4. Generate image variants (thumbnail, medium, large) using Intervention Image
5. Create Media record with metadata
6. Return media ID and URLs for variants

**Image Processing Service**:
```php
class ImageProcessingService
{
    public function processUpload(UploadedFile $file): Media
    {
        // Validate
        $this->validateFile($file);
        
        // Store original
        $path = $file->store('media', 'public');
        
        // Generate variants
        $variants = $this->generateVariants($path);
        
        // Strip EXIF
        $this->stripExif($path);
        
        // Create record
        return Media::create([...]);
    }
    
    private function generateVariants(string $path): array
    {
        return [
            'thumbnail' => $this->resize($path, 150, 150),
            'medium' => $this->resize($path, 300, 300),
            'large' => $this->resize($path, 1024, 1024),
        ];
    }
}
```

**Allowed File Types**:
- Images: JPG, PNG, GIF, WebP
- Documents: PDF, DOC, DOCX

### 5. Comment System with Moderation

**Design Decision**: Nested comments with parent_id, moderation queue, spam detection.

**Comment Model**:
```php
class Comment extends Model
{
    protected $fillable = [
        'post_id', 'user_id', 'parent_id', 'author_name',
        'author_email', 'content', 'status', 'ip_address',
        'user_agent'
    ];
    
    protected $casts = [
        'approved_at' => 'datetime',
    ];
    
    public function post(): BelongsTo;
    public function parent(): BelongsTo;
    public function replies(): HasMany;
    
    // Limit nesting depth
    public function canReply(): bool
    {
        return $this->depth() < 3;
    }
    
    private function depth(): int
    {
        $depth = 0;
        $comment = $this;
        while ($comment->parent) {
            $depth++;
            $comment = $comment->parent;
        }
        return $depth;
    }
}
```

**Comment Statuses**:
- `pending`: Awaiting moderation
- `approved`: Visible publicly
- `spam`: Marked as spam
- `trash`: Soft deleted

**Spam Detection**:
```php
class SpamDetectionService
{
    public function isSpam(string $content, array $context): bool
    {
        // Check link count
        if (substr_count($content, 'http') > 3) {
            return true;
        }
        
        // Check submission speed
        if ($context['time_on_page'] < 3) {
            return true;
        }
        
        // Check blacklisted keywords
        if ($this->containsBlacklistedWords($content)) {
            return true;
        }
        
        // Check honeypot field
        if (!empty($context['honeypot'])) {
            return true;
        }
        
        return false;
    }
}
```

**Rate Limiting**:
- 5 comments per minute per IP address
- Implemented using Laravel's rate limiter in middleware

### 6. Newsletter Subscription Management

**Design Decision**: Double opt-in with email verification, unsubscribe tokens.

**Newsletter Model**:
```php
class Newsletter extends Model
{
    protected $fillable = [
        'email', 'status', 'verification_token',
        'verified_at', 'unsubscribed_at'
    ];
    
    protected $casts = [
        'verified_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];
}
```

**Subscription Flow**:
1. User submits email via form
2. Check for existing subscription
3. Generate unique verification token
4. Send verification email with link
5. User clicks link within 7 days
6. Mark subscription as verified
7. Send confirmation email

**Unsubscribe Flow**:
1. Generate unique unsubscribe token per subscriber
2. Include token in all newsletter emails
3. User clicks unsubscribe link
4. Update status to 'unsubscribed'
5. Display confirmation page

**Export Functionality**:
```php
class NewsletterExportService
{
    public function exportVerified(): string
    {
        $subscribers = Newsletter::where('status', 'verified')->get();
        
        return \League\Csv\Writer::createFromString()
            ->insertOne(['Email', 'Verified At'])
            ->insertAll($subscribers->map(fn($s) => [
                $s->email,
                $s->verified_at->format('Y-m-d H:i:s')
            ]))
            ->toString();
    }
}
```

### 7. Administrative Dashboard

**Design Decision**: Real-time metrics with caching, chart.js for visualizations.

**Dashboard Metrics**:
- Total posts with 30-day comparison
- View counts (today, week, month)
- Pending comments count
- Recent activity feed
- Top 10 posts by views
- Posts published chart (30 days)

**Caching Strategy**:
```php
class DashboardService
{
    public function getMetrics(): array
    {
        return Cache::remember('dashboard.metrics', 600, function () {
            return [
                'total_posts' => $this->getTotalPosts(),
                'views_today' => $this->getViewsToday(),
                'views_week' => $this->getViewsWeek(),
                'views_month' => $this->getViewsMonth(),
                'pending_comments' => Comment::where('status', 'pending')->count(),
                'top_posts' => $this->getTopPosts(),
                'posts_chart' => $this->getPostsChart(),
            ];
        });
    }
}
```

### 8. Search Functionality

**Design Decision**: Full-text search using SQLite FTS5 or Laravel Scout with database driver.

**Search Implementation**:
```php
class SearchService
{
    public function search(string $query, array $filters = []): Collection
    {
        return Post::query()
            ->where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content', 'LIKE', "%{$query}%")
                  ->orWhere('excerpt', 'LIKE', "%{$query}%");
            })
            ->when($filters['category'] ?? null, fn($q, $cat) => 
                $q->where('category_id', $cat)
            )
            ->when($filters['author'] ?? null, fn($q, $author) => 
                $q->where('user_id', $author)
            )
            ->orderByRaw("
                CASE 
                    WHEN title LIKE ? THEN 1
                    WHEN excerpt LIKE ? THEN 2
                    ELSE 3
                END
            ", ["%{$query}%", "%{$query}%"])
            ->paginate(15);
    }
}
```

**Live Search Suggestions**:
- Alpine.js component with debounced input (300ms)
- AJAX request to `/api/search/suggestions`
- Return top 5 matching post titles
- Display in dropdown below search field

### 9. SEO Optimization Features

**Design Decision**: Automatic meta tag generation, sitemap generation, structured data.

**SEO Meta Tags**:
```php
// In Post model or service
public function getMetaTags(): array
{
    return [
        'title' => $this->meta_title ?: $this->title,
        'description' => $this->meta_description ?: Str::limit($this->excerpt, 160),
        'keywords' => $this->meta_keywords,
        'og:title' => $this->title,
        'og:description' => $this->excerpt,
        'og:image' => $this->featured_image_url,
        'og:url' => route('posts.show', $this->slug),
        'twitter:card' => 'summary_large_image',
    ];
}
```

**Sitemap Generation**:
```php
class SitemapService
{
    public function generate(): string
    {
        $sitemap = new \Spatie\Sitemap\Sitemap();
        
        // Add posts
        Post::published()->each(function ($post) use ($sitemap) {
            $sitemap->add(
                Url::create(route('posts.show', $post->slug))
                    ->setLastModificationDate($post->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.8)
            );
        });
        
        // Add categories, pages, tags...
        
        return $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
```

**Structured Data (Schema.org)**:
```php
// In post view
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "{{ $post->title }}",
    "image": "{{ $post->featured_image_url }}",
    "datePublished": "{{ $post->published_at->toIso8601String() }}",
    "dateModified": "{{ $post->updated_at->toIso8601String() }}",
    "author": {
        "@type": "Person",
        "name": "{{ $post->author->name }}"
    }
}
</script>
```

### 10. Responsive Frontend Design

**Design Decision**: Tailwind CSS v3 with mobile-first approach, Alpine.js for interactivity.

**Responsive Breakpoints**:
- Mobile: < 640px (sm)
- Tablet: 640px - 1024px (md, lg)
- Desktop: > 1024px (xl, 2xl)

**Mobile Navigation**:
```html
<nav x-data="{ open: false }">
    <!-- Mobile menu button -->
    <button @click="open = !open" class="md:hidden">
        <svg>...</svg>
    </button>
    
    <!-- Mobile menu -->
    <div x-show="open" class="md:hidden">
        <!-- Menu items -->
    </div>
    
    <!-- Desktop menu -->
    <div class="hidden md:flex">
        <!-- Menu items -->
    </div>
</nav>
```

**Responsive Images**:
```html
<img 
    src="{{ $media->url('medium') }}"
    srcset="
        {{ $media->url('thumbnail') }} 150w,
        {{ $media->url('medium') }} 300w,
        {{ $media->url('large') }} 1024w
    "
    sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
    alt="{{ $media->alt_text }}"
    loading="lazy"
>
```

### 11. API for External Integration

**Design Decision**: RESTful API with Laravel Sanctum for token authentication, API resources for responses.

**API Structure**:
```
GET    /api/posts              - List posts (paginated)
GET    /api/posts/{id}         - Get single post
POST   /api/posts              - Create post (authenticated)
PUT    /api/posts/{id}         - Update post (authenticated)
DELETE /api/posts/{id}         - Delete post (authenticated)
GET    /api/categories         - List categories
GET    /api/tags               - List tags
POST   /api/posts/{id}/like    - Like post (authenticated)
POST   /api/posts/{id}/bookmark - Bookmark post (authenticated)
```

**API Resource**:
```php
class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when($request->routeIs('api.posts.show'), $this->content),
            'featured_image' => $this->featured_image_url,
            'reading_time' => $this->reading_time,
            'published_at' => $this->published_at,
            'author' => [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'tags' => $this->tags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]),
        ];
    }
}
```

**Rate Limiting**:
```php
// In bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->throttleApi('60,1'); // 60 requests per minute
})
```

**API Documentation**:
- Use Laravel Scribe for automatic API documentation
- Generate interactive docs at `/docs`
- Include request/response examples

### 12. Performance Optimization

**Design Decision**: Multi-layer caching strategy, query optimization, asset optimization.

**Caching Strategy**:
1. **Query Result Cache**: Cache expensive queries (10-60 minutes)
2. **View Cache**: Cache rendered views for homepage, category pages (10 minutes)
3. **Model Cache**: Cache frequently accessed models
4. **Settings Cache**: Cache site settings (24 hours)

**Query Optimization**:
```php
// Eager loading to prevent N+1
Post::with(['author', 'category', 'tags'])
    ->published()
    ->latest()
    ->paginate(15);

// Select only needed columns
Post::select(['id', 'title', 'slug', 'excerpt', 'featured_image'])
    ->published()
    ->get();
```

**Asset Optimization**:
- Vite for bundling and minification
- Image lazy loading with `loading="lazy"`
- Critical CSS inlined in head
- Defer non-critical JavaScript
- Cache headers: 1 year for static assets

### 13. Security Measures

**Design Decision**: Defense in depth with multiple security layers.

**Security Headers Middleware**:
```php
class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        return $response
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-XSS-Protection', '1; mode=block')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->header('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
    }
}
```

**Input Sanitization**:
```php
class HtmlSanitizer
{
    public function sanitize(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,b,strong,i,em,u,a[href],ul,ol,li,blockquote,code,pre,h2,h3,h4');
        
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }
}
```

**Rate Limiting**:
- Login: 5 attempts per minute per IP
- Comment submission: 3 per minute per IP
- API: 60 requests per minute
- Implemented using Laravel's RateLimiter

**File Upload Security**:
- Validate MIME type and extension
- Reject executable files (.php, .exe, .sh, etc.)
- Store uploads outside web root
- Generate random filenames

### 14. Content Scheduling

**Design Decision**: Laravel scheduler with command to publish scheduled posts.

**Scheduler Configuration**:
```php
// In routes/console.php or bootstrap/app.php
Schedule::command('posts:publish-scheduled')->everyMinute();
```

**Publish Command**:
```php
class PublishScheduledPostsCommand extends Command
{
    public function handle()
    {
        $posts = Post::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();
        
        foreach ($posts as $post) {
            $post->update([
                'status' => 'published',
                'published_at' => now(),
            ]);
            
            // Queue notification email
            dispatch(new SendPostPublishedNotification($post));
        }
        
        $this->info("Published {$posts->count()} scheduled posts");
    }
}
```

### 15. Analytics and Reporting

**Design Decision**: Custom analytics with session-based duplicate prevention.

**PostView Model**:
```php
class PostView extends Model
{
    protected $fillable = [
        'post_id', 'session_id', 'ip_address',
        'user_agent', 'referer'
    ];
    
    public function post(): BelongsTo;
}
```

**View Tracking**:
```php
class PostViewController
{
    public function trackView(Post $post, Request $request)
    {
        $sessionId = session()->getId();
        
        // Check if already viewed in this session
        $exists = PostView::where('post_id', $post->id)
            ->where('session_id', $sessionId)
            ->exists();
        
        if (!$exists) {
            PostView::create([
                'post_id' => $post->id,
                'session_id' => $sessionId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
            ]);
            
            $post->increment('view_count');
        }
    }
}
```

**Analytics Dashboard**:
- Views over time chart (Chart.js)
- Top posts by views
- Popular categories
- Traffic sources
- Geographic distribution (if IP geolocation added)

### 16. Static Pages Management

**Design Decision**: Flexible page system with template support.

**Page Model**:
```php
class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'content', 'template',
        'parent_id', 'display_order', 'status',
        'meta_title', 'meta_description'
    ];
    
    public function parent(): BelongsTo;
    public function children(): HasMany;
}
```

**Page Templates**:
- `default`: Standard page layout
- `full-width`: No sidebar
- `contact`: Includes contact form
- `about`: Custom about page layout

**Contact Form Handling**:
```php
class ContactMessage extends Model
{
    protected $fillable = [
        'name', 'email', 'subject', 'message',
        'status', 'ip_address'
    ];
}

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email',
            'subject' => 'required|max:255',
            'message' => 'required|max:5000',
        ]);
        
        ContactMessage::create([
            ...$validated,
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);
        
        // Send notification to admin
        Mail::to(config('mail.admin'))->send(new ContactFormSubmitted($validated));
        
        return back()->with('success', 'Message sent successfully');
    }
}
```

### 17. User Management and Roles

**Design Decision**: Role stored as enum in users table, policies for authorization.

**User Model Enhancement**:
```php
class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password', 'role',
        'avatar', 'bio', 'last_login_at'
    ];
    
    protected $casts = [
        'last_login_at' => 'datetime',
    ];
    
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    
    public function isEditor(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }
    
    public function posts(): HasMany;
}
```

**Role Middleware**:
```php
class RoleMiddleware
{
    public function handle($request, Closure $next, string $role)
    {
        if (!$request->user() || $request->user()->role !== $role) {
            abort(403, 'Unauthorized action.');
        }
        
        return $next($request);
    }
}
```

### 18. Image Processing and Optimization

**Design Decision**: Intervention Image for processing, automatic WebP generation.

**Image Processing Pipeline**:
```php
use Intervention\Image\Facades\Image;

class ImageProcessingService
{
    private array $sizes = [
        'thumbnail' => [150, 150],
        'medium' => [300, 300],
        'large' => [1024, 1024],
    ];
    
    public function processImage(string $path): array
    {
        $variants = [];
        
        foreach ($this->sizes as $name => [$width, $height]) {
            $image = Image::make(storage_path("app/public/{$path}"));
            
            // Resize maintaining aspect ratio
            $image->fit($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            // Compress
            $image->encode('jpg', 85);
            
            // Save
            $variantPath = $this->getVariantPath($path, $name);
            $image->save(storage_path("app/public/{$variantPath}"));
            
            // Generate WebP version
            $webpPath = $this->generateWebP($variantPath);
            
            $variants[$name] = [
                'path' => $variantPath,
                'webp_path' => $webpPath,
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        }
        
        // Strip EXIF from original
        $this->stripExif($path);
        
        return $variants;
    }
    
    private function generateWebP(string $path): string
    {
        $image = Image::make(storage_path("app/public/{$path}"));
        $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $path);
        $image->encode('webp', 85)->save(storage_path("app/public/{$webpPath}"));
        return $webpPath;
    }
    
    private function stripExif(string $path): void
    {
        $image = Image::make(storage_path("app/public/{$path}"));
        $image->save(storage_path("app/public/{$path}"));
    }
}
```

### 19. Dark Mode Support

**Design Decision**: CSS variables with localStorage persistence, Alpine.js toggle.

**Dark Mode Implementation**:
```html
<!-- In layout -->
<html x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" 
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      :class="{ 'dark': darkMode }">
<head>
    <script>
        // Prevent flash of unstyled content
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <!-- Dark mode toggle -->
    <button @click="darkMode = !darkMode">
        <svg x-show="!darkMode"><!-- Sun icon --></svg>
        <svg x-show="darkMode"><!-- Moon icon --></svg>
    </button>
</body>
</html>
```

**Tailwind Configuration**:
```js
// tailwind.config.js
module.exports = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                // Custom dark mode colors
            }
        }
    }
}
```

### 20. Social Media Integration

**Design Decision**: Client-side sharing with Web Share API fallback.

**Share Buttons**:
```html
<div x-data="sharePost()">
    <!-- Facebook -->
    <button @click="shareOnFacebook">
        <svg><!-- Facebook icon --></svg>
    </button>
    
    <!-- Twitter -->
    <button @click="shareOnTwitter">
        <svg><!-- Twitter icon --></svg>
    </button>
    
    <!-- Copy Link -->
    <button @click="copyLink">
        <svg><!-- Link icon --></svg>
    </button>
</div>

<script>
function sharePost() {
    return {
        shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        },
        shareOnTwitter() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent(document.title);
            window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
        },
        async copyLink() {
            await navigator.clipboard.writeText(window.location.href);
            alert('Link copied to clipboard!');
        }
    }
}
</script>
```

### 21. Reading Progress Indicator

**Design Decision**: JavaScript scroll tracking with fixed position progress bar.

**Progress Bar Component**:
```html
<div x-data="readingProgress()" 
     x-init="init()"
     class="fixed top-0 left-0 w-full h-1 bg-gray-200 z-50">
    <div class="h-full bg-blue-600 transition-all duration-100"
         :style="`width: ${progress}%`"></div>
</div>

<script>
function readingProgress() {
    return {
        progress: 0,
        init() {
            window.addEventListener('scroll', () => {
                const article = document.querySelector('article');
                const articleTop = article.offsetTop;
                const articleHeight = article.offsetHeight;
                const windowHeight = window.innerHeight;
                const scrollTop = window.scrollY;
                
                const scrolled = scrollTop - articleTop;
                const total = articleHeight - windowHeight;
                
                this.progress = Math.min(100, Math.max(0, (scrolled / total) * 100));
            });
        }
    }
}
</script>
```

### 22. Related Posts Algorithm

**Design Decision**: Weighted scoring algorithm with caching.

**Related Posts Service**:
```php
class RelatedPostsService
{
    public function getRelatedPosts(Post $post, int $limit = 4): Collection
    {
        return Cache::remember("related_posts.{$post->id}", 3600, function () use ($post, $limit) {
            $posts = Post::published()
                ->where('id', '!=', $post->id)
                ->get();
            
            $scored = $posts->map(function ($candidate) use ($post) {
                $score = 0;
                
                // Same category: 40%
                if ($candidate->category_id === $post->category_id) {
                    $score += 40;
                }
                
                // Shared tags: 40%
                $sharedTags = $candidate->tags->pluck('id')
                    ->intersect($post->tags->pluck('id'))
                    ->count();
                $score += ($sharedTags / max(1, $post->tags->count())) * 40;
                
                // Publication date proximity: 20%
                $daysDiff = abs($candidate->published_at->diffInDays($post->published_at));
                $score += max(0, 20 - ($daysDiff / 30) * 20);
                
                return ['post' => $candidate, 'score' => $score];
            });
            
            return $scored->sortByDesc('score')
                ->take($limit)
                ->pluck('post');
        });
    }
}
```

### 23. Comment Reply and Nesting

**Design Decision**: Inline reply forms with Alpine.js, max depth validation.

**Comment Component**:
```html
<div x-data="{ replyFormOpen: false }" 
     class="comment" 
     :style="`margin-left: ${depth * 40}px`">
    
    <div class="comment-content">
        {{ $comment->content }}
    </div>
    
    @if($comment->canReply())
        <button @click="replyFormOpen = !replyFormOpen">Reply</button>
        
        <div x-show="replyFormOpen" x-cloak>
            <form action="{{ route('comments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                <textarea name="content" required></textarea>
                <button type="submit">Submit Reply</button>
                <button type="button" @click="replyFormOpen = false">Cancel</button>
            </form>
        </div>
    @endif
    
    <!-- Nested replies -->
    @foreach($comment->replies as $reply)
        @include('comments.comment', ['comment' => $reply, 'depth' => $depth + 1])
    @endforeach
</div>
```

### 24. Email Notification System

**Design Decision**: Queued jobs for all email notifications, Mailable classes.

**Notification Jobs**:
```php
class SendCommentNotification implements ShouldQueue
{
    use Queueable;
    
    public function __construct(
        private Comment $comment
    ) {}
    
    public function handle()
    {
        // Notify post author
        Mail::to($this->comment->post->author->email)
            ->send(new CommentApprovedMail($this->comment));
        
        // Notify parent commenter if reply
        if ($this->comment->parent) {
            Mail::to($this->comment->parent->author_email)
                ->send(new CommentReplyMail($this->comment));
        }
    }
}
```

**Mailable Classes**:
```php
class CommentApprovedMail extends Mailable
{
    public function __construct(
        public Comment $comment
    ) {}
    
    public function build()
    {
        return $this->subject('New comment on your post')
            ->markdown('emails.comment-approved');
    }
}
```

### 25. Breadcrumb Navigation

**Design Decision**: View composer with breadcrumb generation logic.

**Breadcrumb Service**:
```php
class BreadcrumbService
{
    public function generate(Request $request): array
    {
        $breadcrumbs = [
            ['title' => 'Home', 'url' => route('home')]
        ];
        
        if ($request->route()->named('posts.show')) {
            $post = $request->route('post');
            
            // Add category hierarchy
            $category = $post->category;
            $categoryPath = [];
            while ($category) {
                array_unshift($categoryPath, $category);
                $category = $category->parent;
            }
            
            foreach ($categoryPath as $cat) {
                $breadcrumbs[] = [
                    'title' => $cat->name,
                    'url' => route('categories.show', $cat->slug)
                ];
            }
            
            // Add post
            $breadcrumbs[] = [
                'title' => $post->title,
                'url' => null // Current page
            ];
        }
        
        return $breadcrumbs;
    }
}
```

**Structured Data**:
```php
// In view
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        @foreach($breadcrumbs as $index => $crumb)
        {
            "@type": "ListItem",
            "position": {{ $index + 1 }},
            "name": "{{ $crumb['title'] }}",
            "item": "{{ $crumb['url'] }}"
        }{{ !$loop->last ? ',' : '' }}
        @endforeach
    ]
}
</script>
```

### 26. Post Filtering and Sorting

**Design Decision**: AJAX-based filtering with URL parameter persistence.

**Filter Component**:
```html
<div x-data="postFilter()">
    <!-- Sort dropdown -->
    <select @change="updateFilter('sort', $event.target.value)">
        <option value="latest">Latest</option>
        <option value="popular">Popular</option>
        <option value="oldest">Oldest</option>
    </select>
    
    <!-- Date filter -->
    <select @change="updateFilter('date', $event.target.value)">
        <option value="">All Time</option>
        <option value="today">Today</option>
        <option value="week">This Week</option>
        <option value="month">This Month</option>
    </select>
    
    <!-- Posts container -->
    <div id="posts-container">
        <!-- Posts loaded here -->
    </div>
</div>

<script>
function postFilter() {
    return {
        filters: new URLSearchParams(window.location.search),
        
        updateFilter(key, value) {
            this.filters.set(key, value);
            
            // Update URL
            const newUrl = `${window.location.pathname}?${this.filters.toString()}`;
            history.pushState({}, '', newUrl);
            
            // Fetch filtered posts
            this.fetchPosts();
        },
        
        async fetchPosts() {
            const response = await fetch(`/api/posts?${this.filters.toString()}`);
            const html = await response.text();
            document.getElementById('posts-container').innerHTML = html;
        }
    }
}
</script>
```

### 27. Lazy Loading and Infinite Scroll

**Design Decision**: Intersection Observer API for scroll detection.

**Infinite Scroll Component**:
```html
<div x-data="infiniteScroll()">
    <div id="posts-container">
        @foreach($posts as $post)
            @include('partials.post-card', ['post' => $post])
        @endforeach
    </div>
    
    <div x-ref="sentinel" class="h-20"></div>
    
    <div x-show="loading" class="text-center py-4">
        <svg class="animate-spin"><!-- Spinner --></svg>
    </div>
    
    <div x-show="allLoaded" class="text-center py-4">
        End of content
    </div>
</div>

<script>
function infiniteScroll() {
    return {
        loading: false,
        allLoaded: false,
        page: 2,
        
        init() {
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && !this.loading && !this.allLoaded) {
                    this.loadMore();
                }
            }, { rootMargin: '200px' });
            
            observer.observe(this.$refs.sentinel);
        },
        
        async loadMore() {
            this.loading = true;
            
            const response = await fetch(`/posts?page=${this.page}`);
            const html = await response.text();
            
            if (html.trim() === '') {
                this.allLoaded = true;
            } else {
                document.getElementById('posts-container').insertAdjacentHTML('beforeend', html);
                this.page++;
                
                // Update URL
                history.pushState({}, '', `?page=${this.page}`);
            }
            
            this.loading = false;
        }
    }
}
</script>
```

### 28. Settings Management System

**Design Decision**: Key-value settings table with caching and grouping.

**Setting Model**:
```php
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type'];
    
    protected $casts = [
        'value' => 'json',
    ];
    
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 86400, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }
    
    public static function set(string $key, $value, string $group = 'general')
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
        
        Cache::forget("setting.{$key}");
    }
}
```

**Settings Groups**:
- General: Site name, tagline, timezone
- SEO: Meta defaults, sitemap settings
- Social Media: Social links, sharing options
- Email: SMTP settings, notification preferences
- Comments: Moderation settings, spam thresholds
- Media: Upload limits, image sizes
- Reading: Posts per page, excerpt length
- Appearance: Theme colors, logo

### 29. Menu Builder System

**Design Decision**: Polymorphic menu items with drag-and-drop ordering.

**Menu Models**:
```php
class Menu extends Model
{
    protected $fillable = ['name', 'location'];
    
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('order');
    }
}

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id', 'parent_id', 'title', 'url',
        'type', 'target', 'css_class', 'order'
    ];
    
    public function parent(): BelongsTo;
    public function children(): HasMany;
    
    // Polymorphic relationship for linkable items
    public function linkable(): MorphTo;
}
```

**Menu Builder Interface**:
- Drag-and-drop using SortableJS
- AJAX save on reorder
- Add items from pages, categories, tags, or custom URLs
- Nested menu support with visual indentation

### 30. Widget Management System

**Design Decision**: Widget areas with JSON configuration storage.

**Widget Models**:
```php
class WidgetArea extends Model
{
    protected $fillable = ['name', 'slug', 'description'];
    
    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class)->orderBy('order');
    }
}

class Widget extends Model
{
    protected $fillable = [
        'widget_area_id', 'type', 'title',
        'settings', 'order', 'active'
    ];
    
    protected $casts = [
        'settings' => 'array',
        'active' => 'boolean',
    ];
}
```

**Built-in Widget Types**:
- `recent-posts`: Display recent posts
- `popular-posts`: Display most viewed posts
- `categories`: Category list
- `tags-cloud`: Tag cloud
- `newsletter`: Newsletter signup form
- `search`: Search form
- `custom-html`: Custom HTML content

**Widget Rendering**:
```php
class WidgetService
{
    public function render(Widget $widget): string
    {
        return match($widget->type) {
            'recent-posts' => view('widgets.recent-posts', [
                'posts' => Post::latest()->take($widget->settings['count'] ?? 5)->get()
            ])->render(),
            'popular-posts' => view('widgets.popular-posts', [
                'posts' => Post::orderByDesc('view_count')->take($widget->settings['count'] ?? 5)->get()
            ])->render(),
            // ... other widget types
        };
    }
}
```

### 31. Spam Detection and Prevention

**Design Decision**: Multi-layer spam detection with honeypot, rate limiting, keyword filtering.

**Spam Detection Implementation**:
```php
class SpamDetectionService
{
    private array $blacklistedWords = ['viagra', 'casino', 'lottery', /* ... */];
    
    public function isSpam(array $data): bool
    {
        // Link count check
        if (substr_count($data['content'], 'http') > 3) {
            return true;
        }
        
        // Submission speed check
        if (($data['submitted_at'] - $data['page_loaded_at']) < 3) {
            return true;
        }
        
        // Keyword check
        $content = strtolower($data['content']);
        foreach ($this->blacklistedWords as $word) {
            if (str_contains($content, $word)) {
                return true;
            }
        }
        
        // Honeypot check
        if (!empty($data['website'])) { // Hidden field
            return true;
        }
        
        return false;
    }
}
```

**Rate Limiting**:
```php
// In bootstrap/app.php or middleware
RateLimiter::for('comments', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});
```

### 32. Activity Logging System

**Design Decision**: Trait-based activity logging with model observers.

**ActivityLog Model**:
```php
class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id',
        'description', 'ip_address', 'user_agent',
        'old_values', 'new_values'
    ];
    
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];
    
    public function user(): BelongsTo;
    public function subject(): MorphTo;
}
```

**LogsActivity Trait**:
```php
trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'created',
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'description' => "Created {$model->getTable()} #{$model->id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'new_values' => $model->getAttributes(),
            ]);
        });
        
        static::updated(function ($model) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'updated',
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'description' => "Updated {$model->getTable()} #{$model->id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'old_values' => $model->getOriginal(),
                'new_values' => $model->getChanges(),
            ]);
        });
        
        static::deleted(function ($model) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'deleted',
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'description' => "Deleted {$model->getTable()} #{$model->id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'old_values' => $model->getAttributes(),
            ]);
        });
    }
}
```

**Archiving Old Logs**:
```php
// Scheduled command
Schedule::command('activity-logs:archive')->daily();

class ArchiveActivityLogsCommand extends Command
{
    public function handle()
    {
        $threshold = now()->subDays(90);
        $count = ActivityLog::where('created_at', '<', $threshold)->count();
        
        if ($count > 10000) {
            // Archive to file or separate table
            $logs = ActivityLog::where('created_at', '<', $threshold)->get();
            Storage::put("activity-logs/archive-{$threshold->format('Y-m-d')}.json", $logs->toJson());
            
            // Delete archived logs
            ActivityLog::where('created_at', '<', $threshold)->delete();
            
            $this->info("Archived {$count} activity logs");
        }
    }
}
```

### 33. Backup and Restore System

**Design Decision**: Automated SQLite database backups with cloud storage support.

**Backup Command**:
```php
class BackupDatabaseCommand extends Command
{
    protected $signature = 'db:backup {--cloud : Upload to cloud storage}';
    
    public function handle()
    {
        $dbPath = database_path('database.sqlite');
        $backupName = 'backup-' . now()->format('Y-m-d-His') . '.sqlite';
        $backupPath = storage_path("app/backups/{$backupName}");
        
        // Create backup directory if not exists
        if (!File::exists(storage_path('app/backups'))) {
            File::makeDirectory(storage_path('app/backups'), 0755, true);
        }
        
        // Copy database file
        File::copy($dbPath, $backupPath);
        
        $this->info("Database backed up to: {$backupPath}");
        
        // Upload to cloud if requested
        if ($this->option('cloud') && config('filesystems.cloud')) {
            Storage::cloud()->put("backups/{$backupName}", File::get($backupPath));
            $this->info("Backup uploaded to cloud storage");
        }
        
        // Clean old backups (keep last 30 days)
        $this->cleanOldBackups();
    }
    
    private function cleanOldBackups()
    {
        $files = File::files(storage_path('app/backups'));
        $threshold = now()->subDays(30);
        
        foreach ($files as $file) {
            if (File::lastModified($file) < $threshold->timestamp) {
                File::delete($file);
            }
        }
    }
}
```

**Restore Command**:
```php
class RestoreDatabaseCommand extends Command
{
    protected $signature = 'db:restore {backup : Backup filename}';
    
    public function handle()
    {
        $backupPath = storage_path("app/backups/{$this->argument('backup')}");
        
        if (!File::exists($backupPath)) {
            $this->error("Backup file not found: {$backupPath}");
            return 1;
        }
        
        if (!$this->confirm('This will overwrite the current database. Continue?')) {
            return 0;
        }
        
        $dbPath = database_path('database.sqlite');
        
        // Create backup of current database
        File::copy($dbPath, $dbPath . '.before-restore');
        
        // Restore backup
        File::copy($backupPath, $dbPath);
        
        $this->info("Database restored from: {$backupPath}");
    }
}
```

**Scheduler Configuration**:
```php
Schedule::command('db:backup --cloud')->dailyAt('02:00');
```

### 34. Two-Factor Authentication

**Design Decision**: TOTP-based 2FA with backup codes.

**User Model Enhancement**:
```php
class User extends Authenticatable
{
    protected $fillable = [
        // ... existing fields
        'two_factor_secret', 'two_factor_recovery_codes',
        'two_factor_confirmed_at'
    ];
    
    protected $casts = [
        'two_factor_recovery_codes' => 'array',
        'two_factor_confirmed_at' => 'datetime',
    ];
    
    protected $hidden = [
        'password', 'two_factor_secret', 'two_factor_recovery_codes',
    ];
    
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }
}
```

**2FA Service**:
```php
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    private Google2FA $google2fa;
    
    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }
    
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }
    
    public function getQRCodeUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );
    }
    
    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }
    
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = Str::random(10);
        }
        return $codes;
    }
}
```

**2FA Middleware**:
```php
class RequireTwoFactorAuth
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        
        if ($user && $user->hasTwoFactorEnabled() && !session('2fa_verified')) {
            return redirect()->route('two-factor.challenge');
        }
        
        return $next($request);
    }
}
```

### 35. Content Import and Export

**Design Decision**: Support WordPress XML, JSON, and Markdown formats.

**Import Service**:
```php
class ContentImportService
{
    public function importWordPress(string $xmlPath): array
    {
        $xml = simplexml_load_file($xmlPath);
        $imported = ['posts' => 0, 'categories' => 0, 'tags' => 0];
        
        // Import categories
        foreach ($xml->xpath('//wp:category') as $wpCategory) {
            $category = Category::firstOrCreate([
                'slug' => (string) $wpCategory->{'wp:category_nicename'}
            ], [
                'name' => (string) $wpCategory->{'wp:cat_name'},
                'description' => (string) $wpCategory->{'wp:category_description'},
            ]);
            $imported['categories']++;
        }
        
        // Import posts
        foreach ($xml->channel->item as $item) {
            if ((string) $item->children('wp', true)->post_type !== 'post') {
                continue;
            }
            
            $post = Post::create([
                'title' => (string) $item->title,
                'slug' => Str::slug((string) $item->title),
                'content' => (string) $item->children('content', true)->encoded,
                'excerpt' => (string) $item->children('excerpt', true)->encoded,
                'status' => (string) $item->children('wp', true)->status === 'publish' ? 'published' : 'draft',
                'published_at' => (string) $item->pubDate,
                'user_id' => auth()->id(),
            ]);
            
            // Import tags
            foreach ($item->category as $category) {
                if ((string) $category['domain'] === 'post_tag') {
                    $tag = Tag::firstOrCreate([
                        'slug' => Str::slug((string) $category)
                    ], [
                        'name' => (string) $category
                    ]);
                    $post->tags()->attach($tag->id);
                    $imported['tags']++;
                }
            }
            
            $imported['posts']++;
        }
        
        return $imported;
    }
    
    public function importMarkdown(string $filePath): Post
    {
        $content = File::get($filePath);
        
        // Parse YAML frontmatter
        preg_match('/^---\n(.*?)\n---\n(.*)$/s', $content, $matches);
        $frontmatter = Yaml::parse($matches[1] ?? '');
        $markdown = $matches[2] ?? $content;
        
        return Post::create([
            'title' => $frontmatter['title'] ?? 'Untitled',
            'slug' => $frontmatter['slug'] ?? Str::slug($frontmatter['title'] ?? 'untitled'),
            'content' => Str::markdown($markdown),
            'excerpt' => $frontmatter['excerpt'] ?? '',
            'status' => $frontmatter['status'] ?? 'draft',
            'published_at' => $frontmatter['date'] ?? now(),
            'user_id' => auth()->id(),
        ]);
    }
}
```

**Export Service**:
```php
class ContentExportService
{
    public function exportToJson(): string
    {
        $data = [
            'posts' => Post::with(['author', 'category', 'tags'])->get(),
            'categories' => Category::all(),
            'tags' => Tag::all(),
            'pages' => Page::all(),
        ];
        
        return json_encode($data, JSON_PRETTY_PRINT);
    }
    
    public function exportToZip(): string
    {
        $zip = new ZipArchive();
        $filename = storage_path('app/exports/export-' . now()->format('Y-m-d-His') . '.zip');
        
        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            throw new \Exception('Cannot create zip file');
        }
        
        // Add JSON data
        $zip->addFromString('data.json', $this->exportToJson());
        
        // Add media files
        $media = Media::all();
        foreach ($media as $item) {
            $path = storage_path("app/public/{$item->path}");
            if (File::exists($path)) {
                $zip->addFile($path, "media/{$item->filename}");
            }
        }
        
        $zip->close();
        
        return $filename;
    }
}
```

### 36. Post Revision System

**Design Decision**: Automatic revision creation on update with diff comparison.

**PostRevision Model**:
```php
class PostRevision extends Model
{
    protected $fillable = [
        'post_id', 'user_id', 'title', 'content',
        'excerpt', 'revision_number'
    ];
    
    public function post(): BelongsTo;
    public function author(): BelongsTo;
}
```

**Revision Service**:
```php
class PostRevisionService
{
    private const MAX_REVISIONS = 25;
    
    public function createRevision(Post $post): PostRevision
    {
        $revisionNumber = $post->revisions()->max('revision_number') + 1;
        
        $revision = PostRevision::create([
            'post_id' => $post->id,
            'user_id' => auth()->id(),
            'title' => $post->getOriginal('title'),
            'content' => $post->getOriginal('content'),
            'excerpt' => $post->getOriginal('excerpt'),
            'revision_number' => $revisionNumber,
        ]);
        
        // Clean old revisions
        $this->cleanOldRevisions($post);
        
        return $revision;
    }
    
    private function cleanOldRevisions(Post $post): void
    {
        $count = $post->revisions()->count();
        
        if ($count > self::MAX_REVISIONS) {
            $post->revisions()
                ->orderBy('created_at')
                ->limit($count - self::MAX_REVISIONS)
                ->delete();
        }
    }
    
    public function restore(PostRevision $revision): Post
    {
        $post = $revision->post;
        
        // Create revision of current state before restoring
        $this->createRevision($post);
        
        $post->update([
            'title' => $revision->title,
            'content' => $revision->content,
            'excerpt' => $revision->excerpt,
        ]);
        
        return $post;
    }
    
    public function diff(PostRevision $from, PostRevision $to): array
    {
        return [
            'title' => $this->generateDiff($from->title, $to->title),
            'content' => $this->generateDiff($from->content, $to->content),
        ];
    }
    
    private function generateDiff(string $old, string $new): string
    {
        // Use a diff library like sebastian/diff
        $differ = new \SebastianBergmann\Diff\Differ();
        return $differ->diff($old, $new);
    }
}
```

### 37. Post Series Management

**Design Decision**: Series as separate model with ordered post relationships.

**Series Model**:
```php
class Series extends Model
{
    protected $fillable = ['name', 'slug', 'description'];
    
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)
            ->withPivot('order')
            ->orderBy('order');
    }
}
```

**Series Navigation Component**:
```php
class SeriesNavigationService
{
    public function getNavigation(Post $post): ?array
    {
        $series = $post->series;
        
        if (!$series) {
            return null;
        }
        
        $posts = $series->posts;
        $currentIndex = $posts->search(fn($p) => $p->id === $post->id);
        
        return [
            'series' => $series,
            'current_position' => $currentIndex + 1,
            'total_posts' => $posts->count(),
            'previous' => $posts[$currentIndex - 1] ?? null,
            'next' => $posts[$currentIndex + 1] ?? null,
            'all_posts' => $posts,
        ];
    }
}
```

### 38. Reading List and Bookmarks

**Design Decision**: Bookmark model with user relationship, AJAX toggle.

**Bookmark Model**:
```php
class Bookmark extends Model
{
    protected $fillable = ['user_id', 'post_id'];
    
    public function user(): BelongsTo;
    public function post(): BelongsTo;
}
```

**Bookmark Controller**:
```php
class BookmarkController extends Controller
{
    public function toggle(Post $post)
    {
        $user = auth()->user();
        
        $bookmark = Bookmark::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();
        
        if ($bookmark) {
            $bookmark->delete();
            return response()->json(['bookmarked' => false]);
        }
        
        Bookmark::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
        
        return response()->json(['bookmarked' => true]);
    }
    
    public function index()
    {
        $bookmarks = auth()->user()
            ->bookmarks()
            ->with('post')
            ->latest()
            ->paginate(20);
        
        return view('bookmarks.index', compact('bookmarks'));
    }
}
```

### 39. Advanced Search with Filters

**Design Decision**: Query builder with multiple filter support.

**Advanced Search Service**:
```php
class AdvancedSearchService
{
    public function search(array $filters): Collection
    {
        $query = Post::query()->where('status', 'published');
        
        // Text search
        if (!empty($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'LIKE', "%{$filters['q']}%")
                  ->orWhere('content', 'LIKE', "%{$filters['q']}%");
            });
        }
        
        // Date range
        if (!empty($filters['date_from'])) {
            $query->where('published_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('published_at', '<=', $filters['date_to']);
        }
        
        // Author filter
        if (!empty($filters['author'])) {
            $query->where('user_id', $filters['author']);
        }
        
        // Category filter (including subcategories)
        if (!empty($filters['category'])) {
            $category = Category::find($filters['category']);
            $categoryIds = $this->getCategoryWithDescendants($category);
            $query->whereIn('category_id', $categoryIds);
        }
        
        // Tag filter
        if (!empty($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('tags.id', (array) $filters['tags']);
            });
        }
        
        return $query->with(['author', 'category', 'tags'])
            ->paginate(15);
    }
    
    private function getCategoryWithDescendants(Category $category): array
    {
        $ids = [$category->id];
        
        foreach ($category->children as $child) {
            $ids = array_merge($ids, $this->getCategoryWithDescendants($child));
        }
        
        return $ids;
    }
}
```

### 40. Content Calendar

**Design Decision**: Full calendar view with drag-and-drop scheduling.

**Calendar Controller**:
```php
class ContentCalendarController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        
        $posts = Post::whereYear('published_at', $year)
            ->whereMonth('published_at', $month)
            ->orWhere(function ($q) use ($year, $month) {
                $q->whereYear('scheduled_at', $year)
                  ->whereMonth('scheduled_at', $month);
            })
            ->with('author')
            ->get();
        
        return view('admin.calendar', compact('posts', 'month', 'year'));
    }
    
    public function updateDate(Post $post, Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);
        
        if ($post->status === 'scheduled') {
            $post->update(['scheduled_at' => $validated['date']]);
        } else {
            $post->update(['published_at' => $validated['date']]);
        }
        
        return response()->json(['success' => true]);
    }
}
```

### 41. Notification System

**Design Decision**: Database notifications with real-time updates.

**Notification Model**:
```php
class Notification extends Model
{
    protected $fillable = [
        'user_id', 'type', 'data', 'read_at'
    ];
    
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];
    
    public function user(): BelongsTo;
    
    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
```

**Notification Service**:
```php
class NotificationService
{
    public function notifyNewComment(Comment $comment): void
    {
        Notification::create([
            'user_id' => $comment->post->user_id,
            'type' => 'new_comment',
            'data' => [
                'comment_id' => $comment->id,
                'post_id' => $comment->post_id,
                'commenter_name' => $comment->author_name,
                'message' => "New comment on your post: {$comment->post->title}",
            ],
        ]);
    }
    
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()->whereNull('read_at')->count();
    }
}
```

### 42. GDPR Compliance Features

**Design Decision**: Cookie consent, data export, right to be forgotten.

**GDPR Service**:
```php
class GdprService
{
    public function exportUserData(User $user): array
    {
        return [
            'user' => $user->only(['name', 'email', 'created_at']),
            'posts' => $user->posts,
            'comments' => $user->comments,
            'bookmarks' => $user->bookmarks()->with('post')->get(),
            'activity_logs' => ActivityLog::where('user_id', $user->id)->get(),
        ];
    }
    
    public function deleteUserData(User $user): void
    {
        // Anonymize comments instead of deleting
        $user->comments()->update([
            'author_name' => 'Anonymous',
            'author_email' => 'deleted@example.com',
            'user_id' => null,
        ]);
        
        // Delete bookmarks
        $user->bookmarks()->delete();
        
        // Anonymize activity logs
        ActivityLog::where('user_id', $user->id)->update([
            'user_id' => null,
            'ip_address' => '0.0.0.0',
        ]);
        
        // Delete user account
        $user->delete();
    }
}
```

**Cookie Consent Component**:
```html
<div x-data="cookieConsent()" x-show="!hasConsent" x-cloak>
    <div class="fixed bottom-0 left-0 right-0 bg-gray-900 text-white p-4">
        <p>We use cookies to improve your experience. By using our site, you agree to our cookie policy.</p>
        <div class="flex gap-4 mt-2">
            <button @click="accept" class="btn-primary">Accept</button>
            <button @click="decline" class="btn-secondary">Decline</button>
        </div>
    </div>
</div>

<script>
function cookieConsent() {
    return {
        hasConsent: localStorage.getItem('cookie_consent') !== null,
        
        accept() {
            localStorage.setItem('cookie_consent', 'accepted');
            this.hasConsent = true;
        },
        
        decline() {
            localStorage.setItem('cookie_consent', 'declined');
            this.hasConsent = true;
            // Disable tracking scripts
        }
    }
}
</script>
```

### 43. Performance Monitoring Dashboard

**Design Decision**: Custom performance metrics tracking.

**Performance Metrics Service**:
```php
class PerformanceMetricsService
{
    public function trackPageLoad(float $loadTime, string $url): void
    {
        Cache::increment('perf.page_loads');
        Cache::increment('perf.total_load_time', $loadTime);
    }
    
    public function getAverageLoadTime(): float
    {
        $total = Cache::get('perf.total_load_time', 0);
        $count = Cache::get('perf.page_loads', 1);
        
        return $total / $count;
    }
    
    public function trackSlowQuery(string $sql, float $time): void
    {
        if ($time > 100) { // milliseconds
            Log::warning('Slow query detected', [
                'sql' => $sql,
                'time' => $time,
            ]);
        }
    }
    
    public function getCacheStats(): array
    {
        return [
            'hits' => Cache::get('cache.hits', 0),
            'misses' => Cache::get('cache.misses', 0),
            'ratio' => $this->getCacheHitRatio(),
        ];
    }
    
    private function getCacheHitRatio(): float
    {
        $hits = Cache::get('cache.hits', 0);
        $misses = Cache::get('cache.misses', 0);
        $total = $hits + $misses;
        
        return $total > 0 ? ($hits / $total) * 100 : 0;
    }
}
```

### 44. Sitemap Generation

**Design Decision**: Automatic sitemap with index for large sites.

**Sitemap Service** (Enhanced):
```php
class SitemapService
{
    private const MAX_URLS_PER_SITEMAP = 50000;
    
    public function generate(): void
    {
        $postCount = Post::published()->count();
        
        if ($postCount > self::MAX_URLS_PER_SITEMAP) {
            $this->generateSitemapIndex();
        } else {
            $this->generateSingleSitemap();
        }
    }
    
    private function generateSingleSitemap(): void
    {
        $sitemap = Sitemap::create();
        
        // Add homepage
        $sitemap->add(Url::create('/')
            ->setPriority(1.0)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));
        
        // Add posts
        Post::published()->chunk(1000, function ($posts) use ($sitemap) {
            foreach ($posts as $post) {
                $sitemap->add(
                    Url::create(route('posts.show', $post->slug))
                        ->setLastModificationDate($post->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.8)
                );
            }
        });
        
        // Add categories, pages, tags...
        
        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
    
    private function generateSitemapIndex(): void
    {
        // Generate multiple sitemaps and index
        // Implementation for large sites
    }
}
```

### 45. Rate Limiting and Throttling

**Design Decision**: Laravel's built-in rate limiter with custom limits per endpoint.

**Rate Limit Configuration**:
```php
// In bootstrap/app.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('comments', function (Request $request) {
    return Limit::perMinute(3)->by($request->ip());
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        ->response(function () {
            return response()->json([
                'message' => 'Too many requests',
            ], 429);
        });
});
```

**Rate Limit Middleware Usage**:
```php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::post('/comments', [CommentController::class, 'store'])
    ->middleware('throttle:comments');
```

### 46. Maintenance Mode

**Design Decision**: Laravel's built-in maintenance mode with custom page and bypass.

**Maintenance Controller**:
```php
class MaintenanceController extends Controller
{
    public function enable(Request $request)
    {
        $secret = Str::random(32);
        
        Artisan::call('down', [
            '--secret' => $secret,
            '--retry' => 60,
        ]);
        
        return response()->json([
            'message' => 'Maintenance mode enabled',
            'bypass_url' => url("/{$secret}"),
        ]);
    }
    
    public function disable()
    {
        Artisan::call('up');
        
        return response()->json([
            'message' => 'Maintenance mode disabled',
        ]);
    }
    
    public function addAllowedIp(Request $request)
    {
        $validated = $request->validate([
            'ip' => 'required|ip',
        ]);
        
        // Store allowed IPs in config or database
        Setting::set('maintenance.allowed_ips', 
            array_merge(
                Setting::get('maintenance.allowed_ips', []),
                [$validated['ip']]
            )
        );
    }
}
```

**Custom Maintenance Page**:
```blade
{{-- resources/views/errors/503.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Site Maintenance</title>
    <style>
        body {
            font-family: sans-serif;
            text-align: center;
            padding: 50px;
        }
    </style>
</head>
<body>
    <h1>We'll be back soon!</h1>
    <p>We're performing scheduled maintenance. We'll be back online shortly.</p>
    @if(isset($retryAfter))
        <p>Please check back in {{ $retryAfter }} seconds.</p>
    @endif
</body>
</html>
```

### 47. Broken Link Checker

**Design Decision**: Scheduled job to check external links.

**Broken Link Checker Job**:
```php
class CheckBrokenLinks implements ShouldQueue
{
    use Queueable;
    
    public function handle()
    {
        Post::published()->chunk(100, function ($posts) {
            foreach ($posts as $post) {
                $this->checkPostLinks($post);
            }
        });
    }
    
    private function checkPostLinks(Post $post): void
    {
        preg_match_all('/<a\s+href="(https?:\/\/[^"]+)"/', $post->content, $matches);
        $links = $matches[1] ?? [];
        
        foreach ($links as $url) {
            try {
                $response = Http::timeout(10)->get($url);
                
                if ($response->failed()) {
                    $this->reportBrokenLink($post, $url, $response->status());
                }
            } catch (\Exception $e) {
                $this->reportBrokenLink($post, $url, 0);
            }
        }
    }
    
    private function reportBrokenLink(Post $post, string $url, int $statusCode): void
    {
        BrokenLink::updateOrCreate([
            'post_id' => $post->id,
            'url' => $url,
        ], [
            'status_code' => $statusCode,
            'last_checked_at' => now(),
        ]);
    }
}
```

**BrokenLink Model**:
```php
class BrokenLink extends Model
{
    protected $fillable = [
        'post_id', 'url', 'status_code',
        'last_checked_at', 'fixed_at', 'ignored'
    ];
    
    protected $casts = [
        'last_checked_at' => 'datetime',
        'fixed_at' => 'datetime',
        'ignored' => 'boolean',
    ];
    
    public function post(): BelongsTo;
}
```

### 48. Image Alt Text Validation

**Design Decision**: Pre-save validation with warnings.

**Alt Text Validator**:
```php
class AltTextValidator
{
    public function validate(string $content): array
    {
        $issues = [];
        
        preg_match_all('/<img[^>]+>/', $content, $matches);
        
        foreach ($matches[0] as $img) {
            if (!preg_match('/alt=["\']([^"\']*)["\']/', $img, $altMatch)) {
                $issues[] = 'Image missing alt attribute';
            } elseif (empty(trim($altMatch[1]))) {
                $issues[] = 'Image has empty alt attribute';
            }
        }
        
        return $issues;
    }
}
```

**Post Form Request**:
```php
class StorePostRequest extends FormRequest
{
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $altTextValidator = new AltTextValidator();
            $issues = $altTextValidator->validate($this->content);
            
            if (!empty($issues)) {
                session()->flash('alt_text_warnings', $issues);
            }
        });
    }
}
```

### 49. Multi-language Support

**Design Decision**: Laravel localization with language switcher.

**Language Configuration**:
```php
// config/app.php
'locale' => 'en',
'fallback_locale' => 'en',
'available_locales' => ['en', 'es', 'fr', 'de', 'ar'],
```

**Language Middleware**:
```php
class SetLocale
{
    public function handle($request, Closure $next)
    {
        $locale = $request->cookie('locale') 
            ?? $request->user()?->locale 
            ?? config('app.locale');
        
        if (in_array($locale, config('app.available_locales'))) {
            app()->setLocale($locale);
        }
        
        return $next($request);
    }
}
```

**Post Translation**:
```php
class PostTranslation extends Model
{
    protected $fillable = [
        'post_id', 'locale', 'title', 'content', 'excerpt'
    ];
    
    public function post(): BelongsTo;
}

// In Post model
public function translations(): HasMany
{
    return $this->hasMany(PostTranslation::class);
}

public function translate(string $locale): ?PostTranslation
{
    return $this->translations()->where('locale', $locale)->first();
}
```

**RTL Support**:
```html
<html dir="{{ in_array(app()->getLocale(), ['ar', 'he']) ? 'rtl' : 'ltr' }}">
```

### 50. Progressive Web App Features

**Design Decision**: Service worker for offline support, web manifest for installability.

**Web Manifest** (`public/manifest.json`):
```json
{
    "name": "TechNewsHub",
    "short_name": "TechNews",
    "description": "Technology news and blog platform",
    "start_url": "/",
    "display": "standalone",
    "background_color": "#ffffff",
    "theme_color": "#3b82f6",
    "icons": [
        {
            "src": "/images/icon-192.png",
            "sizes": "192x192",
            "type": "image/png"
        },
        {
            "src": "/images/icon-512.png",
            "sizes": "512x512",
            "type": "image/png"
        }
    ]
}
```

**Service Worker** (`public/sw.js`):
```javascript
const CACHE_NAME = 'technewshub-v1';
const urlsToCache = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/offline.html'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                return fetch(event.request)
                    .catch(() => caches.match('/offline.html'));
            })
    );
});
```

**Service Worker Registration**:
```javascript
// In app.js
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
        .then(registration => console.log('SW registered'))
        .catch(error => console.log('SW registration failed', error));
}
```

## Data Models

### Database Schema Overview

**Core Tables**:
- `users`: User accounts with roles
- `posts`: Blog posts and articles
- `categories`: Hierarchical post categories
- `tags`: Flat tag system
- `post_tag`: Pivot table for post-tag relationships
- `comments`: User comments with nesting
- `media`: Media library files
- `pages`: Static pages
- `newsletters`: Newsletter subscriptions
- `settings`: Site configuration
- `activity_logs`: Audit trail
- `post_views`: Analytics tracking
- `post_revisions`: Version history
- `bookmarks`: User reading lists
- `series`: Post series
- `notifications`: User notifications
- `contact_messages`: Contact form submissions
- `broken_links`: Link checker results
- `menus` & `menu_items`: Navigation menus
- `widget_areas` & `widgets`: Sidebar widgets

### Key Relationships

```
User
├── hasMany: Posts (as author)
├── hasMany: Comments
├── hasMany: Bookmarks
├── hasMany: Notifications
└── hasMany: ActivityLogs

Post
├── belongsTo: User (author)
├── belongsTo: Category
├── belongsToMany: Tags
├── hasMany: Comments
├── hasMany: PostViews
├── hasMany: PostRevisions
├── hasMany: Bookmarks
└── belongsToMany: Series

Category
├── belongsTo: Category (parent)
├── hasMany: Category (children)
└── hasMany: Posts

Comment
├── belongsTo: Post
├── belongsTo: User
├── belongsTo: Comment (parent)
└── hasMany: Comment (replies)
```

## Error Handling

### Exception Handling Strategy

1. **Validation Errors**: Return 422 with detailed field errors
2. **Authentication Errors**: Redirect to login with intended URL
3. **Authorization Errors**: Return 403 with error message
4. **Not Found Errors**: Return 404 with custom page
5. **Server Errors**: Log error, return 500 with generic message (hide details in production)

### Custom Exception Handler

```php
class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            return response()->view('errors.404', [], 404);
        }
        
        if ($exception instanceof AuthorizationException) {
            return response()->view('errors.403', [], 403);
        }
        
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception instanceof ValidationException 
                    ? $exception->errors() 
                    : null,
            ], $this->getStatusCode($exception));
        }
        
        return parent::render($request, $exception);
    }
}
```

### Logging Strategy

- **Error Logs**: All exceptions logged to `storage/logs/laravel.log`
- **Activity Logs**: User actions logged to database
- **Performance Logs**: Slow queries logged separately
- **Security Logs**: Failed login attempts, rate limit violations

## Testing Strategy

### Test Coverage Goals

- **Unit Tests**: 80% coverage for services, models
- **Feature Tests**: All API endpoints, critical user flows
- **Browser Tests**: Key user journeys (optional)

### Test Structure

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── PostServiceTest.php
│   │   ├── ImageProcessingServiceTest.php
│   │   └── SpamDetectionServiceTest.php
│   └── Models/
│       ├── PostTest.php
│       └── CommentTest.php
└── Feature/
    ├── Auth/
    │   ├── LoginTest.php
    │   └── RegistrationTest.php
    ├── Admin/
    │   ├── PostManagementTest.php
    │   └── UserManagementTest.php
    └── Api/
        ├── PostApiTest.php
        └── AuthenticationTest.php
```

### Testing Approach

1. **Database**: Use in-memory SQLite for tests
2. **Factories**: Create test data using model factories
3. **Assertions**: Test both happy paths and edge cases
4. **Mocking**: Mock external services (email, HTTP requests)
5. **Coverage**: Run `php artisan test --coverage` to check coverage

### Example Test

```php
class PostServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_calculate_reading_time()
    {
        $service = new PostService();
        $content = str_repeat('word ', 200); // 200 words
        
        $readingTime = $service->calculateReadingTime($content);
        
        $this->assertEquals(1, $readingTime); // 1 minute
    }
    
    public function test_publish_scheduled_posts()
    {
        $post = Post::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);
        
        Artisan::call('posts:publish-scheduled');
        
        $post->refresh();
        $this->assertEquals('published', $post->status);
        $this->assertNotNull($post->published_at);
    }
}
```

## Design Rationale

### Key Design Decisions

1. **SQLite Database**: Chosen for simplicity and portability. Suitable for small to medium traffic sites. Can migrate to MySQL/PostgreSQL if needed.

2. **Blade Templates**: Native Laravel templating with Alpine.js for interactivity. Avoids complexity of SPA frameworks while maintaining good UX.

3. **Tailwind CSS**: Utility-first approach enables rapid development and consistent design. Mobile-first by default.

4. **Service Layer**: Business logic extracted to service classes for testability and reusability.

5. **Job Queues**: Asynchronous processing for emails, image processing, and scheduled tasks prevents blocking requests.

6. **Caching Strategy**: Multi-layer caching (query, view, model) balances performance with data freshness.

7. **API Resources**: Consistent API responses with controlled data exposure.

8. **Policy-Based Authorization**: Centralized authorization logic, easy to test and maintain.

9. **Trait-Based Activity Logging**: Automatic audit trail without cluttering controllers.

10. **Repository Pattern (Optional)**: Can be added later if data source abstraction needed.

### Performance Considerations

- Eager loading to prevent N+1 queries
- Database indexing on frequently queried columns
- Query result caching for expensive operations
- Image optimization and lazy loading
- Asset bundling and minification
- CDN integration for static assets (future)

### Security Considerations

- CSRF protection on all forms
- XSS prevention through output escaping
- SQL injection prevention via Eloquent ORM
- Rate limiting on sensitive endpoints
- File upload validation and sanitization
- Security headers middleware
- Password hashing with bcrypt
- Two-factor authentication support

### Scalability Considerations

- Horizontal scaling: Stateless application design
- Database: Can migrate from SQLite to MySQL/PostgreSQL
- Caching: Redis/Memcached for distributed caching
- Queue workers: Multiple workers for job processing
- CDN: Offload static assets
- Load balancing: Multiple application servers

