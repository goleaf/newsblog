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
        });

        static::deleted(function ($tag) {
            // Regenerate sitemap when tag is deleted
            \Illuminate\Support\Facades\App::make(\App\Services\SitemapService::class)->regenerateIfNeeded();
        });
    }
}
