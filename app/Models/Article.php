<?php

namespace App\Models;

/**
 * Article is an alias of Post to match spec terminology.
 * It inherits all relationships, casts, scopes, and accessors.
 */
class Article extends Post
{
    protected $table = 'posts';

    /**
     * Author alias for the existing user() relationship.
     */
    public function author()
    {
        return $this->user();
    }

    public function tags()
    {
        // Ensure pivot table and keys match posts' pivot
        return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_post', 'post_id', 'category_id')
            ->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id')->where('status', 'approved');
    }

    public function views()
    {
        return $this->hasMany(PostView::class, 'post_id');
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, 'post_id');
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class, 'post_id');
    }

    public function revisions()
    {
        return $this->hasMany(PostRevision::class, 'post_id')->orderBy('created_at', 'desc');
    }

    public function brokenLinks()
    {
        return $this->hasMany(BrokenLink::class, 'post_id');
    }

    public function socialShares()
    {
        return $this->hasMany(SocialShare::class, 'post_id');
    }
}
