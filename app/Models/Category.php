<?php

namespace App\Models;

use App\Services\SearchIndexService;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'icon',
        'color_code',
        'meta_title',
        'meta_description',
        'status',
        'display_order',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('display_order');
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeParent($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function getPostsCount()
    {
        return $this->posts()->published()->count();
    }

    public function getUrlAttribute()
    {
        return route('category.show', $this->slug);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::created(function ($category) {
            // Regenerate sitemap when category is created
            App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();
        });

        static::updated(function ($category) {
            // Invalidate search index cache when category is updated
            // Check if search-relevant fields changed
            if ($category->isDirty(['name', 'description'])) {
                App::make(SearchIndexService::class)->invalidateSearchCaches();
            }

            // Regenerate sitemap if slug or status changed
            if ($category->isDirty(['slug', 'status'])) {
                App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();
            }
        });

        static::deleted(function ($category) {
            // Invalidate search index cache when category is deleted
            App::make(SearchIndexService::class)->invalidateSearchCaches();

            // Regenerate sitemap
            App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();
        });
    }
}
