<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }

    public function getPostsCount()
    {
        return $this->posts()->published()->count();
    }

    public function getUrlAttribute()
    {
        return route('tag.show', $this->slug);
    }

    /**
     * Get SEO meta tags for the tag.
     */
    public function getMetaTags(): array
    {
        $url = route('tag.show', $this->slug);

        return [
            // Basic meta tags
            'title' => '#'.$this->name.' - Tagged Articles - '.config('app.name', 'TechNewsHub'),
            'description' => $this->getMetaDescription(),
            'keywords' => null,

            // Open Graph tags
            'og:title' => '#'.$this->name.' - Tagged Articles',
            'og:description' => $this->getMetaDescription(),
            'og:image' => asset('images/default-og-image.jpg'),
            'og:url' => $url,
            'og:type' => 'website',
            'og:site_name' => config('app.name', 'TechNewsHub'),

            // Twitter Card tags
            'twitter:card' => 'summary',
            'twitter:title' => '#'.$this->name.' - Tagged Articles',
            'twitter:description' => $this->getMetaDescription(),
            'twitter:image' => asset('images/default-og-image.jpg'),
            'twitter:url' => $url,
        ];
    }

    /**
     * Get meta description with validation (max 160 chars).
     */
    public function getMetaDescription(): string
    {
        $description = $this->description ? Str::limit(strip_tags($this->description), 160, '') : '';

        // Ensure it doesn't exceed 160 characters
        return Str::limit($description, 160, '') ?: 'Browse articles tagged with '.$this->name;
    }

    /**
     * Get Schema.org CollectionPage structured data.
     */
    public function getStructuredData(): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => '#'.$this->name,
            'description' => $this->getMetaDescription(),
            'url' => route('tag.show', $this->slug),
        ];

        if ($this->description) {
            $data['about'] = [
                '@type' => 'Thing',
                'name' => $this->name,
                'description' => $this->description,
            ];
        }

        return $data;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::created(function ($tag) {
            // Regenerate sitemap when tag is created
            \Illuminate\Support\Facades\App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updated(function ($tag) {
            // Regenerate sitemap if slug changed
            if ($tag->isDirty('slug')) {
                \Illuminate\Support\Facades\App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();
            }

            // Invalidate view caches (Requirement 12.3)
            \Illuminate\Support\Facades\App::make(\App\Services\CacheService::class)->invalidateTag($tag->id);
        });

        static::deleted(function ($tag) {
            // Regenerate sitemap when tag is deleted
            \Illuminate\Support\Facades\App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();

            // Invalidate view caches (Requirement 12.3)
            \Illuminate\Support\Facades\App::make(\App\Services\CacheService::class)->invalidateTag($tag->id);
        });
    }
}
