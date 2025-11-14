<?php

namespace App\Models;

use App\Services\SearchIndexService;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'image_alt_text',
        'status',
        'is_featured',
        'is_trending',
        'view_count',
        'published_at',
        'scheduled_at',
        'reading_time',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_post')->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->where('status', 'approved');
    }

    public function views()
    {
        return $this->hasMany(PostView::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function isBookmarkedBy($userId): bool
    {
        if (! $userId) {
            return false;
        }

        return $this->bookmarks()->where('user_id', $userId)->exists();
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    public function revisions()
    {
        return $this->hasMany(PostRevision::class)->orderBy('created_at', 'desc');
    }

    public function series()
    {
        return $this->belongsToMany(Series::class, 'post_series')
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('post_series.order');
    }

    public function brokenLinks()
    {
        return $this->hasMany(BrokenLink::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeTrending($query)
    {
        return $query->where('is_trending', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '>', now());
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByTag($query, $tagId)
    {
        return $query->whereHas('tags', function ($q) use ($tagId) {
            $q->where('tags.id', $tagId);
        });
    }

    public function scopeByAuthor($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    public function scopePopular($query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    public function scopeWithoutContent($query)
    {
        return $query->whereNotNull('title')
            ->where(function ($q) {
                $q->whereNull('content')
                    ->orWhere('content', '')
                    ->orWhereRaw("TRIM(content) = ''");
            });
    }

    public function getFormattedDateAttribute()
    {
        return $this->published_at ? $this->published_at->format('M d, Y') : ($this->created_at ? $this->created_at->format('M d, Y') : null);
    }

    public function getExcerptLimitedAttribute()
    {
        return Str::limit($this->excerpt, 150);
    }

    public function getReadingTimeTextAttribute()
    {
        return $this->reading_time ? $this->reading_time.' min read' : null;
    }

    public function getFeaturedImageUrlAttribute()
    {
        return $this->featured_image ? asset('storage/'.$this->featured_image) : null;
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function isPublished()
    {
        return $this->status === 'published' && $this->published_at <= now();
    }

    public function canBeEditedBy($user)
    {
        if ($user->isAdmin() || $user->isEditor()) {
            return true;
        }

        return $this->user_id === $user->id;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }

            if (empty($post->reading_time)) {
                $post->reading_time = static::calculateReadingTime($post->content);
            }
        });

        static::created(function ($post) {
            // Invalidate search index cache when post is created
            if ($post->isPublished()) {
                App::make(SearchIndexService::class)->invalidateSearchCaches();

                // Invalidate related posts cache for posts in the same category
                if ($post->category_id) {
                    App::make(\App\Services\RelatedPostsService::class)->invalidateCacheByCategory($post->category_id);
                }

                // Regenerate sitemap
                App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();

                // Invalidate view caches (Requirement 12.3)
                App::make(\App\Services\CacheService::class)->invalidateHomepage();
                if ($post->category_id) {
                    App::make(\App\Services\CacheService::class)->invalidateCategory($post->category_id);
                }
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('title') && empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }

            if ($post->isDirty('content')) {
                $post->reading_time = static::calculateReadingTime($post->content);
            }
        });

        static::updated(function ($post) {
            // Invalidate search index cache when post is updated
            // Check if any search-relevant fields changed
            $searchRelevantFields = ['title', 'excerpt', 'content', 'status', 'published_at', 'category_id'];
            $hasSearchRelevantChanges = false;

            foreach ($searchRelevantFields as $field) {
                if ($post->isDirty($field)) {
                    $hasSearchRelevantChanges = true;
                    break;
                }
            }

            if ($hasSearchRelevantChanges) {
                App::make(SearchIndexService::class)->invalidateSearchCaches();

                // Invalidate related posts cache for this post and related posts
                App::make(\App\Services\RelatedPostsService::class)->invalidateCache($post);

                // Invalidate view caches (Requirement 12.3)
                $cacheService = App::make(\App\Services\CacheService::class);
                $cacheService->invalidateHomepage();
                $cacheService->invalidatePost($post->id);
                $cacheService->invalidatePost($post->slug);

                // If category changed, invalidate cache for both old and new categories
                if ($post->isDirty('category_id')) {
                    $oldCategoryId = $post->getOriginal('category_id');
                    if ($oldCategoryId) {
                        App::make(\App\Services\RelatedPostsService::class)->invalidateCacheByCategory($oldCategoryId);
                        $cacheService->invalidateCategory($oldCategoryId);
                    }
                    if ($post->category_id) {
                        App::make(\App\Services\RelatedPostsService::class)->invalidateCacheByCategory($post->category_id);
                        $cacheService->invalidateCategory($post->category_id);
                    }
                } elseif ($post->category_id) {
                    // If title or excerpt changed, invalidate category cache
                    if ($post->isDirty('title') || $post->isDirty('excerpt')) {
                        App::make(\App\Services\RelatedPostsService::class)->invalidateCacheByCategory($post->category_id);
                        $cacheService->invalidateCategory($post->category_id);
                    }
                }

                // Regenerate sitemap if post status or publication changed
                if ($post->isDirty(['status', 'published_at', 'slug'])) {
                    App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();
                }
            }
        });

        static::deleted(function ($post) {
            // Invalidate search index cache when post is deleted
            App::make(SearchIndexService::class)->invalidateSearchCaches();

            // Invalidate related posts cache for posts in the same category
            if ($post->category_id) {
                App::make(\App\Services\RelatedPostsService::class)->invalidateCacheByCategory($post->category_id);
            }

            // Regenerate sitemap
            App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();

            // Invalidate view caches (Requirement 12.3)
            $cacheService = App::make(\App\Services\CacheService::class);
            $cacheService->invalidateHomepage();
            $cacheService->invalidatePost($post->id);
            $cacheService->invalidatePost($post->slug);
            if ($post->category_id) {
                $cacheService->invalidateCategory($post->category_id);
            }
        });
    }

    protected static function calculateReadingTime($content)
    {
        $wordCount = str_word_count(strip_tags($content));

        return (int) ceil($wordCount / 200);
    }

    public function getBookmarksCountAttribute(): int
    {
        return $this->bookmarks()->count();
    }

    /**
     * Get SEO meta tags for the post
     */
    public function getMetaTags(): array
    {
        $url = route('post.show', $this->slug);
        $imageUrl = $this->featured_image_url ?? asset('images/default-og-image.jpg');

        return [
            // Basic meta tags
            'title' => $this->meta_title ?: $this->title,
            'description' => $this->getMetaDescription(),
            'keywords' => $this->meta_keywords,

            // Open Graph tags
            'og:title' => $this->meta_title ?: $this->title,
            'og:description' => $this->getMetaDescription(),
            'og:image' => $imageUrl,
            'og:url' => $url,
            'og:type' => 'article',
            'og:site_name' => config('app.name', 'TechNewsHub'),

            // Open Graph article tags
            'article:published_time' => $this->published_at?->toIso8601String(),
            'article:modified_time' => $this->updated_at->toIso8601String(),
            'article:author' => $this->user->name,
            'article:section' => $this->category->name,
            'article:tag' => $this->tags->pluck('name')->toArray(),

            // Twitter Card tags
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $this->meta_title ?: $this->title,
            'twitter:description' => $this->getMetaDescription(),
            'twitter:image' => $imageUrl,
            'twitter:url' => $url,
        ];
    }

    /**
     * Get meta description with validation (max 160 chars)
     */
    public function getMetaDescription(): string
    {
        $description = $this->meta_description ?: Str::limit(strip_tags($this->excerpt ?: $this->content), 160, '');

        // Ensure it doesn't exceed 160 characters
        return Str::limit($description, 160, '');
    }

    /**
     * Get Schema.org Article structured data
     */
    public function getStructuredData(): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $this->title,
            'description' => $this->getMetaDescription(),
            'image' => $this->featured_image_url ?? asset('images/default-og-image.jpg'),
            'datePublished' => $this->published_at?->toIso8601String(),
            'dateModified' => $this->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $this->user->name,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'TechNewsHub'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.png'),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('post.show', $this->slug),
            ],
        ];

        // Add article section (category)
        if ($this->category) {
            $data['articleSection'] = $this->category->name;
        }

        // Add keywords
        if ($this->tags->count() > 0) {
            $data['keywords'] = $this->tags->pluck('name')->implode(', ');
        }

        // Add word count
        $data['wordCount'] = str_word_count(strip_tags($this->content));

        // Add reading time
        if ($this->reading_time) {
            $data['timeRequired'] = 'PT'.$this->reading_time.'M';
        }

        return $data;
    }
}
