<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'status',
        'template',
        'display_order',
        'parent_id',
    ];

    public function getUrlAttribute()
    {
        return route('page.show', $this->slug);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('title');
    }

    public function parent()
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Page::class, 'parent_id')->ordered();
    }

    public function getAvailableTemplates(): array
    {
        return [
            'default' => 'Default',
            'full-width' => 'Full Width',
            'contact' => 'Contact',
            'about' => 'About',
        ];
    }

    public function isContactTemplate(): bool
    {
        return $this->template === 'contact';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });

        static::created(function ($page) {
            // Regenerate sitemap when page is created
            \Illuminate\Support\Facades\App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();
        });

        static::updating(function ($page) {
            if ($page->isDirty('title') && empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });

        static::updated(function ($page) {
            // Regenerate sitemap if slug or status changed
            if ($page->isDirty(['slug', 'status'])) {
                \Illuminate\Support\Facades\App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();
            }
        });

        static::deleted(function ($page) {
            // Regenerate sitemap when page is deleted
            \Illuminate\Support\Facades\App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();
        });
    }
}
