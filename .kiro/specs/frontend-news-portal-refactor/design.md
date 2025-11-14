# Design Document

## Overview

This design document outlines the architecture and implementation strategy for refactoring the TechNewsHub frontend into a modern, feature-rich news portal. The design leverages existing backend capabilities while introducing a component-based architecture, progressive enhancement, and mobile-first responsive design.

### Design Goals

1. **Maximize Backend Utilization**: Fully leverage all 18 models, 21+ services, and extensive API capabilities
2. **Component-Based Architecture**: Create reusable Blade components following Laravel conventions
3. **Progressive Enhancement**: Build functional core experience, then enhance with JavaScript
4. **Mobile-First Responsive**: Optimize for mobile devices first, then scale up
5. **Performance Optimized**: Achieve sub-2-second page loads with lazy loading and caching
6. **Accessibility Compliant**: Meet WCAG 2.1 AA standards throughout
7. **SEO Optimized**: Implement semantic HTML, meta tags, and structured data
8. **Maintainable Codebase**: Follow Laravel best practices and consistent patterns

### Technology Stack

- **Backend**: Laravel 12, PHP 8.4, existing services and models
- **Frontend**: Blade templates, Tailwind CSS 3, Alpine.js 3
- **Build**: Vite 7 with code splitting and optimization
- **Icons**: Heroicons for consistent iconography
- **Images**: Intervention Image with WebP support
- **Search**: Existing FuzzySearchService with autocomplete
- **Caching**: Existing CacheService for view and query caching

## Architecture

### Component Hierarchy

```
layouts/
├── app.blade.php (main layout)
├── guest.blade.php (unauthenticated)
└── navigation.blade.php (header/nav)

pages/
├── home.blade.php (homepage)
├── posts/
│   ├── index.blade.php (article list)
│   ├── show.blade.php (article detail)
│   └── search.blade.php (search results)
├── categories/
│   ├── index.blade.php (category list)
│   └── show.blade.php (category posts)
├── tags/
│   └── show.blade.php (tag posts)
├── series/
│   ├── index.blade.php (series list)
│   └── show.blade.php (series detail)
├── user/
│   ├── dashboard.blade.php (user dashboard)
│   ├── bookmarks.blade.php (saved articles)
│   └── profile.blade.php (user profile)
└── static/
    ├── about.blade.php
    └── contact.blade.php

components/
├── layout/
│   ├── header.blade.php
│   ├── footer.blade.php
│   ├── sidebar.blade.php
│   └── mobile-menu.blade.php
├── navigation/
│   ├── main-nav.blade.php
│   ├── category-menu.blade.php
│   ├── breadcrumbs.blade.php
│   └── pagination.blade.php
├── content/
│   ├── hero-post.blade.php
│   ├── post-card.blade.php
│   ├── post-grid.blade.php
│   ├── post-list.blade.php
│   ├── trending-posts.blade.php
│   └── related-posts.blade.php
├── article/
│   ├── article-header.blade.php
│   ├── article-content.blade.php
│   ├── article-footer.blade.php
│   ├── article-meta.blade.php
│   ├── reading-progress.blade.php
│   └── floating-actions.blade.php
├── engagement/
│   ├── reaction-buttons.blade.php
│   ├── bookmark-button.blade.php
│   ├── share-modal.blade.php
│   ├── comment-form.blade.php
│   ├── comment-thread.blade.php
│   └── comment-item.blade.php
├── discovery/
│   ├── search-bar.blade.php
│   ├── search-autocomplete.blade.php
│   ├── filter-panel.blade.php
│   ├── sort-dropdown.blade.php
│   └── category-grid.blade.php
├── widgets/
│   ├── recent-posts.blade.php
│   ├── popular-posts.blade.php
│   ├── categories-list.blade.php
│   ├── tags-cloud.blade.php
│   ├── newsletter-form.blade.php
│   └── custom-html.blade.php
├── ui/
│   ├── skeleton-loader.blade.php
│   ├── loading-spinner.blade.php
│   ├── error-message.blade.php
│   ├── empty-state.blade.php
│   ├── modal.blade.php
│   ├── toast-notification.blade.php
│   └── badge.blade.php
└── seo/
    ├── meta-tags.blade.php
    ├── structured-data.blade.php
    └── og-tags.blade.php
```


### Data Flow Architecture

```
User Request
    ↓
Route (web.php)
    ↓
Controller (PostController, CategoryController, etc.)
    ↓
Service Layer (PostService, FuzzySearchService, CacheService, etc.)
    ↓
Model Layer (Post, Category, User, Comment, etc.)
    ↓
Database (SQLite/MySQL with optimized indexes)
    ↓
← Data returned to Controller
    ↓
View Composer (inject common data)
    ↓
Blade Template (with components)
    ↓
Alpine.js (client-side interactivity)
    ↓
User Interface
```

### State Management

**Server-Side State (Laravel Session)**
- User authentication status
- User preferences (theme, filters)
- Flash messages
- CSRF tokens

**Client-Side State (Alpine.js)**
- UI state (modals, dropdowns, menus)
- Form validation state
- Loading states
- Optimistic UI updates

**Persistent State (LocalStorage)**
- Theme preference (light/dark)
- Reading progress
- Dismissed notifications
- Recent searches

**URL State (Query Parameters)**
- Search queries
- Active filters
- Pagination
- Sorting options

## Components and Interfaces

### 1. Homepage Components

#### Hero Post Component
```blade
<x-content.hero-post :post="$featuredPost" />
```

**Props:**
- `$post` (Post model) - Featured post to display

**Features:**
- Large featured image with lazy loading
- Post title, excerpt, category badge
- Author info with avatar
- Reading time and publish date
- Call-to-action button
- Responsive image sizes

#### Trending Posts Component
```blade
<x-content.trending-posts :posts="$trendingPosts" :limit="5" />
```

**Props:**
- `$posts` (Collection) - Trending posts
- `$limit` (int) - Number of posts to show

**Features:**
- Horizontal scrollable on mobile
- Grid layout on desktop
- Trending badge with rank
- View count and reaction count
- Thumbnail images

#### Latest Articles Grid
```blade
<x-content.post-grid :posts="$latestPosts" :columns="3" />
```

**Props:**
- `$posts` (Collection) - Posts to display
- `$columns` (int) - Grid columns (1-4)

**Features:**
- Responsive grid (1 col mobile, 2 tablet, 3+ desktop)
- Post cards with images, titles, excerpts
- Category badges, author info
- Reading time, date, view count
- Hover effects and transitions

#### Category Showcase
```blade
<x-discovery.category-grid :categories="$categories" />
```

**Props:**
- `$categories` (Collection) - Active categories

**Features:**
- Grid of category cards
- Category icons with colors
- Post counts
- Hover effects
- Links to category pages

### 2. Search Components

#### Search Bar with Autocomplete
```blade
<x-discovery.search-bar 
    :placeholder="'Search articles...'"
    :show-filters="true"
/>
```

**Props:**
- `$placeholder` (string) - Input placeholder
- `$showFilters` (bool) - Show filter button

**Alpine.js State:**
```javascript
{
    query: '',
    results: [],
    loading: false,
    showDropdown: false,
    selectedIndex: -1
}
```

**Features:**
- Debounced autocomplete (300ms)
- Keyboard navigation (arrow keys, enter, escape)
- Highlighted matching text
- Recent searches
- Popular searches
- Loading indicator

#### Filter Panel
```blade
<x-discovery.filter-panel 
    :categories="$categories"
    :authors="$authors"
    :active-filters="$activeFilters"
/>
```

**Props:**
- `$categories` (Collection) - Available categories
- `$authors` (Collection) - Available authors
- `$activeFilters` (array) - Currently active filters

**Features:**
- Category multi-select
- Author multi-select
- Date range picker
- Reading time slider
- Active filter badges
- Clear all button
- URL parameter sync

### 3. Article Reading Components

#### Article Header
```blade
<x-article.article-header :post="$post" />
```

**Props:**
- `$post` (Post model) - Article to display

**Features:**
- Article title with proper heading hierarchy
- Category badge with link
- Author info with avatar and bio link
- Publish date and last updated
- Reading time estimate
- View count
- Share button
- Bookmark button

#### Article Content
```blade
<x-article.article-content :post="$post" />
```

**Props:**
- `$post` (Post model) - Article content

**Features:**
- Optimized typography
- Lazy-loaded images with captions
- Code syntax highlighting
- Table of contents (auto-generated from headings)
- Reading progress indicator
- Responsive embeds (videos, tweets)

#### Floating Action Bar
```blade
<x-article.floating-actions :post="$post" />
```

**Props:**
- `$post` (Post model) - Current article

**Alpine.js State:**
```javascript
{
    isVisible: false,
    isBookmarked: false,
    reactions: {},
    showShareModal: false
}
```

**Features:**
- Appears on scroll
- Bookmark button with animation
- Reaction buttons (6 types)
- Share button
- Scroll to top button
- Sticky positioning

#### Series Navigation
```blade
<x-article.series-navigation :post="$post" :series="$series" />
```

**Props:**
- `$post` (Post model) - Current article
- `$series` (Series model) - Article series

**Features:**
- Previous/next article links
- Series progress indicator
- Article thumbnails
- Series title and description
- All articles dropdown

### 4. Engagement Components

#### Reaction Buttons
```blade
<x-engagement.reaction-buttons 
    :post="$post"
    :user-reaction="$userReaction"
/>
```

**Props:**
- `$post` (Post model) - Article
- `$userReaction` (string|null) - User's current reaction

**Alpine.js State:**
```javascript
{
    reactions: {
        like: 0,
        love: 0,
        laugh: 0,
        wow: 0,
        sad: 0,
        angry: 0
    },
    userReaction: null,
    showPicker: false
}
```

**Features:**
- 6 reaction types with animated icons
- Real-time count updates
- Optimistic UI updates
- Hover preview
- API integration
- Login prompt for guests

#### Comment Thread
```blade
<x-engagement.comment-thread 
    :post="$post"
    :comments="$comments"
/>
```

**Props:**
- `$post` (Post model) - Article
- `$comments` (Collection) - Top-level comments

**Features:**
- Nested threading (3 levels)
- Reply buttons
- Edit/delete for own comments
- Moderation indicators
- Real-time updates
- Pagination
- Sort options (newest, oldest, popular)

#### Comment Form
```blade
<x-engagement.comment-form 
    :post="$post"
    :parent-id="null"
/>
```

**Props:**
- `$post` (Post model) - Article
- `$parentId` (int|null) - Parent comment ID for replies

**Alpine.js State:**
```javascript
{
    content: '',
    submitting: false,
    errors: {},
    preview: false
}
```

**Features:**
- Markdown support with preview
- Character counter
- Validation
- Spam detection
- Guest name/email fields
- Login prompt
- Success feedback

### 5. User Dashboard Components

#### Stats Cards
```blade
<x-user.stats-cards :user="$user" />
```

**Props:**
- `$user` (User model) - Current user

**Features:**
- Total bookmarks count
- Total comments count
- Total reactions given
- Total reading time
- Animated counters
- Icon indicators

#### Activity Feed
```blade
<x-user.activity-feed 
    :activities="$activities"
    :limit="10"
/>
```

**Props:**
- `$activities` (Collection) - User activities
- `$limit` (int) - Items to show

**Features:**
- Timeline layout
- Activity types (bookmark, comment, reaction)
- Timestamps
- Article links
- Load more button
- Empty state

#### Bookmark Collections
```blade
<x-user.bookmark-collections 
    :collections="$collections"
    :bookmarks="$bookmarks"
/>
```

**Props:**
- `$collections` (Collection) - User's collections
- `$bookmarks` (Collection) - Bookmarked posts

**Features:**
- Collection management (create, rename, delete)
- Drag-and-drop organization
- Collection filtering
- Bookmark removal
- Share collection
- Export options


## Data Models

### Frontend Data Structures

#### Post Card Data
```php
[
    'id' => int,
    'title' => string,
    'slug' => string,
    'excerpt' => string,
    'featured_image' => string|null,
    'image_alt_text' => string|null,
    'category' => [
        'id' => int,
        'name' => string,
        'slug' => string,
        'icon' => string|null,
        'color_code' => string|null,
    ],
    'author' => [
        'id' => int,
        'name' => string,
        'avatar_url' => string,
    ],
    'published_at' => Carbon,
    'reading_time' => int,
    'view_count' => int,
    'is_featured' => bool,
    'is_trending' => bool,
    'reactions_count' => int,
    'comments_count' => int,
    'bookmarks_count' => int,
]
```

#### Search Result Data
```php
[
    'query' => string,
    'results' => [
        [
            'id' => int,
            'title' => string,
            'slug' => string,
            'excerpt' => string,
            'highlighted_title' => string,
            'highlighted_excerpt' => string,
            'context' => string,
            'relevance_score' => float,
            'type' => 'post'|'tag'|'category',
        ]
    ],
    'total' => int,
    'suggestions' => array,
    'filters' => array,
    'execution_time' => float,
]
```

#### User Dashboard Data
```php
[
    'user' => User,
    'stats' => [
        'bookmarks_count' => int,
        'comments_count' => int,
        'reactions_count' => int,
        'total_reading_time' => int,
    ],
    'recent_bookmarks' => Collection,
    'recent_comments' => Collection,
    'recent_reactions' => Collection,
    'reading_progress' => [
        'series_id' => [
            'completed' => int,
            'total' => int,
            'percentage' => float,
        ]
    ],
]
```

### API Response Formats

#### Standard Success Response
```json
{
    "success": true,
    "data": {},
    "message": "Operation successful",
    "meta": {
        "timestamp": "2025-11-14T10:30:00Z",
        "version": "1.0"
    }
}
```

#### Standard Error Response
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Validation failed",
        "details": {
            "field": ["Error message"]
        }
    },
    "meta": {
        "timestamp": "2025-11-14T10:30:00Z"
    }
}
```

#### Paginated Response
```json
{
    "success": true,
    "data": [],
    "pagination": {
        "current_page": 1,
        "per_page": 12,
        "total": 100,
        "last_page": 9,
        "from": 1,
        "to": 12,
        "has_more": true
    }
}
```

## Error Handling

### Error Types and Handling Strategy

#### Network Errors
- **Detection**: Fetch API catch blocks, timeout detection
- **User Feedback**: Toast notification with retry button
- **Fallback**: Show cached content if available
- **Logging**: Log to console in development, send to error tracking in production

#### Validation Errors
- **Detection**: Server response with 422 status
- **User Feedback**: Inline field errors with red borders
- **Fallback**: Preserve user input, highlight problematic fields
- **Logging**: Log validation rules that failed

#### Authentication Errors
- **Detection**: 401/403 status codes
- **User Feedback**: Modal prompting login/registration
- **Fallback**: Redirect to login with return URL
- **Logging**: Log unauthorized access attempts

#### Not Found Errors (404)
- **Detection**: 404 status code
- **User Feedback**: Custom 404 page with search and suggestions
- **Fallback**: Show popular articles and categories
- **Logging**: Log 404s for broken link detection

#### Server Errors (500)
- **Detection**: 500 status code
- **User Feedback**: Generic error page with support contact
- **Fallback**: Show cached version if available
- **Logging**: Full error details to error tracking service

### Error Component Design

```blade
<x-ui.error-message 
    :type="'network'"
    :message="'Unable to load content'"
    :retry-action="'loadPosts'"
/>
```

**Error Types:**
- `network` - Connection issues
- `validation` - Form validation
- `auth` - Authentication required
- `not-found` - Resource not found
- `server` - Server error
- `timeout` - Request timeout

## Testing Strategy

### Component Testing

#### Unit Tests (PHPUnit)
- Test Blade component rendering
- Test component props validation
- Test component slots
- Test conditional rendering
- Test data transformation

**Example:**
```php
public function test_post_card_renders_correctly()
{
    $post = Post::factory()->create();
    
    $view = $this->blade('<x-content.post-card :post="$post" />', [
        'post' => $post
    ]);
    
    $view->assertSee($post->title);
    $view->assertSee($post->excerpt);
    $view->assertSee($post->category->name);
}
```

#### Integration Tests
- Test full page rendering
- Test component interactions
- Test data flow from controller to view
- Test caching behavior
- Test API integrations

**Example:**
```php
public function test_homepage_displays_featured_content()
{
    $featuredPost = Post::factory()->featured()->create();
    $trendingPosts = Post::factory()->trending()->count(5)->create();
    
    $response = $this->get('/');
    
    $response->assertOk();
    $response->assertSee($featuredPost->title);
    $response->assertViewHas('trendingPosts', function ($posts) {
        return $posts->count() === 5;
    });
}
```

### Frontend Testing

#### Browser Tests (Laravel Dusk)
- Test user interactions
- Test form submissions
- Test JavaScript functionality
- Test responsive behavior
- Test accessibility

**Example:**
```php
public function test_user_can_bookmark_article()
{
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $this->browse(function (Browser $browser) use ($user, $post) {
        $browser->loginAs($user)
            ->visit("/posts/{$post->slug}")
            ->click('@bookmark-button')
            ->waitForText('Bookmarked')
            ->assertSee('Bookmarked');
    });
}
```

#### JavaScript Tests (Jest)
- Test Alpine.js components
- Test utility functions
- Test API client functions
- Test state management

**Example:**
```javascript
test('search autocomplete debounces input', async () => {
    const component = Alpine.component('search-bar');
    
    component.query = 'test';
    await new Promise(resolve => setTimeout(resolve, 100));
    expect(component.loading).toBe(false);
    
    await new Promise(resolve => setTimeout(resolve, 300));
    expect(component.loading).toBe(true);
});
```

### Accessibility Testing

#### Automated Tests
- Run axe-core on all pages
- Test keyboard navigation
- Test screen reader compatibility
- Test color contrast
- Test ARIA attributes

**Example:**
```php
public function test_homepage_is_accessible()
{
    $response = $this->get('/');
    
    $response->assertOk();
    
    // Run axe-core accessibility tests
    $this->assertAccessible($response);
}
```

#### Manual Testing Checklist
- [ ] Keyboard navigation works on all interactive elements
- [ ] Screen reader announces all content correctly
- [ ] Focus indicators are visible
- [ ] Color contrast meets WCAG AA standards
- [ ] Forms have proper labels and error messages
- [ ] Images have descriptive alt text
- [ ] Videos have captions
- [ ] Skip links work correctly

### Performance Testing

#### Metrics to Track
- First Contentful Paint (FCP) < 1.5s
- Largest Contentful Paint (LCP) < 2.5s
- Time to Interactive (TTI) < 3.5s
- Cumulative Layout Shift (CLS) < 0.1
- First Input Delay (FID) < 100ms

#### Testing Tools
- Lighthouse CI for automated performance testing
- WebPageTest for detailed performance analysis
- Chrome DevTools for profiling
- Laravel Debugbar for backend performance

**Example Test:**
```php
public function test_homepage_loads_within_performance_budget()
{
    $startTime = microtime(true);
    
    $response = $this->get('/');
    
    $endTime = microtime(true);
    $loadTime = ($endTime - $startTime) * 1000;
    
    $this->assertLessThan(500, $loadTime, 'Homepage should load in under 500ms');
}
```


## Responsive Design Strategy

### Breakpoint System

```css
/* Tailwind CSS Breakpoints */
sm: 640px   /* Small tablets and large phones */
md: 768px   /* Tablets */
lg: 1024px  /* Small laptops */
xl: 1280px  /* Desktops */
2xl: 1536px /* Large desktops */
```

### Layout Patterns by Breakpoint

#### Mobile (< 640px)
- Single column layout
- Stacked navigation
- Full-width images
- Hamburger menu
- Bottom navigation bar for key actions
- Swipe gestures for navigation

#### Tablet (640px - 1024px)
- Two column layout for article lists
- Collapsible sidebar
- Horizontal category navigation
- Touch-optimized spacing
- Slide-out menus

#### Desktop (> 1024px)
- Three column layout (content + sidebars)
- Persistent navigation
- Mega menus for categories
- Hover interactions
- Keyboard shortcuts

### Component Responsive Behavior

#### Post Grid
```blade
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach($posts as $post)
        <x-content.post-card :post="$post" />
    @endforeach
</div>
```

#### Navigation
```blade
<!-- Mobile: Hamburger menu -->
<div class="lg:hidden">
    <button @click="mobileMenuOpen = true">
        <x-heroicon-o-bars-3 class="w-6 h-6" />
    </button>
</div>

<!-- Desktop: Full navigation -->
<nav class="hidden lg:flex space-x-8">
    <a href="/">Home</a>
    <a href="/categories">Categories</a>
    <a href="/series">Series</a>
</nav>
```

#### Images
```blade
<img 
    src="{{ $post->featured_image_url }}"
    srcset="
        {{ $post->featured_image_url }}?w=400 400w,
        {{ $post->featured_image_url }}?w=800 800w,
        {{ $post->featured_image_url }}?w=1200 1200w
    "
    sizes="
        (max-width: 640px) 100vw,
        (max-width: 1024px) 50vw,
        33vw
    "
    loading="lazy"
    alt="{{ $post->image_alt_text }}"
/>
```

## Performance Optimization

### Image Optimization Strategy

#### Responsive Images
- Generate multiple sizes on upload (thumbnail, medium, large)
- Serve WebP with JPEG fallback
- Use srcset and sizes attributes
- Implement lazy loading
- Use blur-up placeholder technique

#### Implementation
```php
// ImageProcessingService generates:
- thumbnail: 300x200
- medium: 800x600
- large: 1200x800
- webp versions of each
```

### Code Splitting

#### Route-Based Splitting
```javascript
// vite.config.js
export default {
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['alpinejs'],
                    'search': ['./resources/js/search.js'],
                    'comments': ['./resources/js/comments.js'],
                    'dashboard': ['./resources/js/dashboard.js'],
                }
            }
        }
    }
}
```

### Caching Strategy

#### Browser Caching
```php
// Cache headers for static assets
'Cache-Control' => 'public, max-age=31536000, immutable'

// Cache headers for HTML pages
'Cache-Control' => 'public, max-age=600, must-revalidate'
```

#### Server-Side Caching
```php
// Homepage cache (10 minutes)
Cache::remember('homepage', 600, function () {
    return [
        'featuredPost' => Post::featured()->first(),
        'trendingPosts' => Post::trending()->limit(5)->get(),
        'latestPosts' => Post::published()->latest()->limit(12)->get(),
        'categories' => Category::active()->withCount('posts')->get(),
    ];
});

// Category page cache (15 minutes)
Cache::remember("category.{$slug}", 900, function () use ($slug) {
    return Category::where('slug', $slug)
        ->with(['posts' => function ($query) {
            $query->published()->latest()->limit(12);
        }])
        ->firstOrFail();
});

// Post page cache (30 minutes)
Cache::remember("post.{$slug}", 1800, function () use ($slug) {
    return Post::where('slug', $slug)
        ->with(['category', 'author', 'tags', 'comments'])
        ->firstOrFail();
});
```

### Lazy Loading

#### Images
```blade
<img 
    src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 600'%3E%3C/svg%3E"
    data-src="{{ $post->featured_image_url }}"
    loading="lazy"
    class="lazy"
/>
```

#### Components
```blade
<!-- Load comments only when scrolled into view -->
<div x-intersect="loadComments = true">
    <template x-if="loadComments">
        <x-engagement.comment-thread :post="$post" />
    </template>
</div>
```

### Critical CSS

#### Inline Critical CSS
```blade
<style>
    /* Critical above-the-fold styles */
    .header { /* ... */ }
    .hero { /* ... */ }
    .nav { /* ... */ }
</style>

<!-- Defer non-critical CSS -->
<link rel="preload" href="/css/app.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="/css/app.css"></noscript>
```

## SEO Implementation

### Meta Tags Strategy

#### Base Meta Tags
```blade
<x-seo.meta-tags :post="$post" />

<!-- Generates: -->
<title>{{ $post->meta_title ?: $post->title }} | {{ config('app.name') }}</title>
<meta name="description" content="{{ $post->getMetaDescription() }}">
<meta name="keywords" content="{{ $post->meta_keywords }}">
<link rel="canonical" href="{{ route('post.show', $post->slug) }}">
```

#### Open Graph Tags
```blade
<x-seo.og-tags :post="$post" />

<!-- Generates: -->
<meta property="og:title" content="{{ $post->title }}">
<meta property="og:description" content="{{ $post->getMetaDescription() }}">
<meta property="og:image" content="{{ $post->featured_image_url }}">
<meta property="og:url" content="{{ route('post.show', $post->slug) }}">
<meta property="og:type" content="article">
<meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
<meta property="article:author" content="{{ $post->author->name }}">
<meta property="article:section" content="{{ $post->category->name }}">
```

#### Structured Data
```blade
<x-seo.structured-data :post="$post" />

<!-- Generates: -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "{{ $post->title }}",
    "description": "{{ $post->getMetaDescription() }}",
    "image": "{{ $post->featured_image_url }}",
    "datePublished": "{{ $post->published_at->toIso8601String() }}",
    "dateModified": "{{ $post->updated_at->toIso8601String() }}",
    "author": {
        "@type": "Person",
        "name": "{{ $post->author->name }}"
    },
    "publisher": {
        "@type": "Organization",
        "name": "{{ config('app.name') }}",
        "logo": {
            "@type": "ImageObject",
            "url": "{{ asset('images/logo.png') }}"
        }
    }
}
</script>
```

### Sitemap Generation

```php
// SitemapService generates XML sitemap
public function generate(): string
{
    $posts = Post::published()->get();
    $categories = Category::active()->get();
    $pages = Page::published()->get();
    
    return view('sitemap', compact('posts', 'categories', 'pages'))->render();
}
```

## Accessibility Implementation

### Semantic HTML

```blade
<!-- Use proper heading hierarchy -->
<article>
    <header>
        <h1>{{ $post->title }}</h1>
        <p class="text-gray-600">
            <time datetime="{{ $post->published_at->toIso8601String() }}">
                {{ $post->published_at->format('F j, Y') }}
            </time>
        </p>
    </header>
    
    <div class="prose">
        {!! $post->content !!}
    </div>
    
    <footer>
        <nav aria-label="Article tags">
            @foreach($post->tags as $tag)
                <a href="{{ route('tag.show', $tag->slug) }}">{{ $tag->name }}</a>
            @endforeach
        </nav>
    </footer>
</article>
```

### ARIA Attributes

```blade
<!-- Navigation with ARIA -->
<nav aria-label="Main navigation">
    <ul role="list">
        <li><a href="/" aria-current="{{ request()->is('/') ? 'page' : 'false' }}">Home</a></li>
        <li><a href="/categories">Categories</a></li>
    </ul>
</nav>

<!-- Search with ARIA -->
<div role="search">
    <label for="search-input" class="sr-only">Search articles</label>
    <input 
        id="search-input"
        type="search"
        aria-label="Search articles"
        aria-describedby="search-help"
        aria-autocomplete="list"
        aria-controls="search-results"
    />
    <div id="search-help" class="sr-only">
        Type to search articles. Use arrow keys to navigate results.
    </div>
    <div id="search-results" role="listbox" aria-live="polite">
        <!-- Results here -->
    </div>
</div>

<!-- Modal with ARIA -->
<div 
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title"
    aria-describedby="modal-description"
>
    <h2 id="modal-title">Share Article</h2>
    <p id="modal-description">Choose a platform to share this article</p>
</div>
```

### Keyboard Navigation

```javascript
// Alpine.js keyboard navigation
Alpine.data('searchBar', () => ({
    selectedIndex: -1,
    results: [],
    
    handleKeydown(event) {
        switch(event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.selectedIndex = Math.min(
                    this.selectedIndex + 1,
                    this.results.length - 1
                );
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                break;
            case 'Enter':
                event.preventDefault();
                if (this.selectedIndex >= 0) {
                    this.selectResult(this.results[this.selectedIndex]);
                }
                break;
            case 'Escape':
                this.closeResults();
                break;
        }
    }
}));
```

### Focus Management

```blade
<!-- Skip to main content link -->
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-blue-600 focus:text-white">
    Skip to main content
</a>

<main id="main-content" tabindex="-1">
    <!-- Main content -->
</main>

<!-- Focus trap in modal -->
<div x-data="{ open: false }" x-trap="open">
    <button @click="open = true">Open Modal</button>
    
    <div x-show="open" @keydown.escape="open = false">
        <!-- Modal content -->
        <button @click="open = false">Close</button>
    </div>
</div>
```

### Screen Reader Support

```blade
<!-- Visually hidden but available to screen readers -->
<span class="sr-only">{{ $post->view_count }} views</span>

<!-- Live regions for dynamic updates -->
<div aria-live="polite" aria-atomic="true">
    <span x-text="bookmarkStatus"></span>
</div>

<!-- Descriptive button labels -->
<button aria-label="Bookmark this article">
    <x-heroicon-o-bookmark class="w-5 h-5" />
</button>

<!-- Image descriptions -->
<img 
    src="{{ $post->featured_image_url }}"
    alt="{{ $post->image_alt_text ?: 'Featured image for ' . $post->title }}"
/>
```


## Dark Mode Implementation

### Theme System Architecture

#### CSS Variables Approach
```css
/* resources/css/app.css */
:root {
    /* Light mode (default) */
    --color-bg-primary: #ffffff;
    --color-bg-secondary: #f3f4f6;
    --color-text-primary: #111827;
    --color-text-secondary: #6b7280;
    --color-border: #e5e7eb;
    --color-accent: #3b82f6;
}

.dark {
    /* Dark mode */
    --color-bg-primary: #1f2937;
    --color-bg-secondary: #111827;
    --color-text-primary: #f9fafb;
    --color-text-secondary: #9ca3af;
    --color-border: #374151;
    --color-accent: #60a5fa;
}

/* Usage */
.card {
    background-color: var(--color-bg-primary);
    color: var(--color-text-primary);
    border-color: var(--color-border);
}
```

#### Tailwind Dark Mode Configuration
```javascript
// tailwind.config.js
module.exports = {
    darkMode: 'class', // Use class-based dark mode
    theme: {
        extend: {
            colors: {
                // Custom color palette for dark mode
            }
        }
    }
}
```

#### Alpine.js Theme Toggle
```javascript
// resources/js/theme.js
Alpine.data('themeToggle', () => ({
    theme: localStorage.getItem('theme') || 'system',
    
    init() {
        this.applyTheme();
        
        // Watch for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', () => {
                if (this.theme === 'system') {
                    this.applyTheme();
                }
            });
    },
    
    toggle() {
        const themes = ['light', 'dark', 'system'];
        const currentIndex = themes.indexOf(this.theme);
        this.theme = themes[(currentIndex + 1) % themes.length];
        localStorage.setItem('theme', this.theme);
        this.applyTheme();
    },
    
    applyTheme() {
        const isDark = this.theme === 'dark' || 
            (this.theme === 'system' && 
             window.matchMedia('(prefers-color-scheme: dark)').matches);
        
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}));
```

#### Theme Toggle Component
```blade
<div x-data="themeToggle">
    <button 
        @click="toggle"
        class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700"
        aria-label="Toggle theme"
    >
        <!-- Light mode icon -->
        <x-heroicon-o-sun 
            class="w-5 h-5 text-yellow-500" 
            x-show="theme === 'light'"
        />
        
        <!-- Dark mode icon -->
        <x-heroicon-o-moon 
            class="w-5 h-5 text-blue-500" 
            x-show="theme === 'dark'"
        />
        
        <!-- System mode icon -->
        <x-heroicon-o-computer-desktop 
            class="w-5 h-5 text-gray-500" 
            x-show="theme === 'system'"
        />
    </button>
</div>
```

### Dark Mode Styling Patterns

#### Component Dark Mode Styles
```blade
<!-- Post Card with dark mode -->
<article class="bg-white dark:bg-gray-800 rounded-lg shadow-md dark:shadow-gray-900 overflow-hidden">
    <img 
        src="{{ $post->featured_image_url }}"
        alt="{{ $post->image_alt_text }}"
        class="w-full h-48 object-cover dark:opacity-90"
    />
    
    <div class="p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
            {{ $post->title }}
        </h2>
        
        <p class="text-gray-600 dark:text-gray-400 mb-4">
            {{ $post->excerpt }}
        </p>
        
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-500">
                {{ $post->reading_time }} min read
            </span>
            
            <a 
                href="{{ route('post.show', $post->slug) }}"
                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
            >
                Read more →
            </a>
        </div>
    </div>
</article>
```

## Animation and Transitions

### Transition Utilities

```css
/* Smooth transitions for theme changes */
* {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

/* Disable transitions on page load */
.preload * {
    transition: none !important;
}
```

### Alpine.js Transitions

```blade
<!-- Fade in/out -->
<div 
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    Content
</div>

<!-- Slide down -->
<div 
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform -translate-y-4"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform -translate-y-4"
>
    Dropdown content
</div>

<!-- Scale -->
<div 
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
>
    Modal content
</div>
```

### Loading Animations

```blade
<!-- Skeleton loader -->
<div class="animate-pulse">
    <div class="h-48 bg-gray-300 dark:bg-gray-700 rounded-lg mb-4"></div>
    <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
    <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-1/2"></div>
</div>

<!-- Spinner -->
<div class="flex justify-center items-center">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
</div>

<!-- Progress bar -->
<div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
    <div 
        class="bg-blue-600 h-2 rounded-full transition-all duration-300"
        :style="`width: ${progress}%`"
    ></div>
</div>
```

## Security Considerations

### XSS Prevention

```blade
<!-- Always escape user input -->
<h1>{{ $post->title }}</h1>

<!-- Use {!! !!} only for trusted HTML -->
<div class="prose">
    {!! $post->sanitized_content !!}
</div>

<!-- Sanitize in controller -->
public function show(Post $post)
{
    $post->sanitized_content = app(HtmlSanitizer::class)->sanitize($post->content);
    return view('posts.show', compact('post'));
}
```

### CSRF Protection

```blade
<!-- All forms must include CSRF token -->
<form method="POST" action="{{ route('comments.store') }}">
    @csrf
    <textarea name="content"></textarea>
    <button type="submit">Submit</button>
</form>

<!-- AJAX requests must include CSRF token -->
<script>
    fetch('/api/bookmark', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ post_id: 123 })
    });
</script>
```

### Content Security Policy

```php
// Middleware to set CSP headers
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('Content-Security-Policy', 
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
        "style-src 'self' 'unsafe-inline'; " .
        "img-src 'self' data: https:; " .
        "font-src 'self' data:; " .
        "connect-src 'self';"
    );
    
    return $response;
}
```

## Deployment Considerations

### Build Process

```bash
# Production build
npm run build

# Optimize images
php artisan media:optimize

# Clear and warm caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate sitemap
php artisan sitemap:generate
```

### Environment Configuration

```env
# Production settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://technewshub.com

# Asset optimization
VITE_MINIFY=true
VITE_SOURCEMAPS=false

# Caching
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# CDN
ASSET_URL=https://cdn.technewshub.com
```

### Performance Monitoring

```php
// Track page load times
public function handle($request, Closure $next)
{
    $start = microtime(true);
    
    $response = $next($request);
    
    $duration = (microtime(true) - $start) * 1000;
    
    if ($duration > 1000) {
        Log::warning('Slow page load', [
            'url' => $request->fullUrl(),
            'duration' => $duration,
            'user_id' => auth()->id(),
        ]);
    }
    
    return $response;
}
```

## Migration Strategy

### Phase 1: Core Components (Week 1-2)
- Create base layout components
- Implement navigation system
- Build post card components
- Set up responsive grid system

### Phase 2: Homepage (Week 2-3)
- Hero section with featured post
- Trending posts section
- Latest articles grid
- Category showcase
- Infinite scroll

### Phase 3: Article Pages (Week 3-4)
- Article header and content
- Reading progress indicator
- Floating action bar
- Series navigation
- Related posts sidebar

### Phase 4: Search & Discovery (Week 4-5)
- Search bar with autocomplete
- Filter panel
- Search results page
- Category and tag pages
- Advanced filtering

### Phase 5: User Features (Week 5-6)
- User dashboard
- Bookmark management
- Profile pages
- Comment system
- Reaction system

### Phase 6: Polish & Optimization (Week 6-7)
- Dark mode implementation
- Performance optimization
- Accessibility audit
- SEO optimization
- Testing and bug fixes

### Phase 7: Launch Preparation (Week 7-8)
- Final testing
- Documentation
- Deployment preparation
- Monitoring setup
- Launch

## Success Metrics

### Performance Metrics
- First Contentful Paint < 1.5s
- Largest Contentful Paint < 2.5s
- Time to Interactive < 3.5s
- Cumulative Layout Shift < 0.1
- First Input Delay < 100ms

### User Engagement Metrics
- Average session duration > 3 minutes
- Pages per session > 3
- Bounce rate < 50%
- Comment rate > 2%
- Bookmark rate > 5%

### Technical Metrics
- Lighthouse score > 90
- Accessibility score > 95
- SEO score > 95
- Test coverage > 80%
- Zero critical bugs

### Business Metrics
- Page views increase by 50%
- User registrations increase by 30%
- Newsletter signups increase by 40%
- Social shares increase by 60%
- Return visitor rate > 40%

