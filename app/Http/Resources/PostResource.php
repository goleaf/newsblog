<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primaryCategory = $this->whenLoaded('category', function () {
            return [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ];
        });

        // Merge primary category into all categories list for convenience
        $allCategories = collect();
        if ($this->relationLoaded('categories')) {
            $allCategories = $this->categories->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                ];
            });
        }
        if ($this->relationLoaded('category') && $this->category) {
            $allCategories = $allCategories->prepend([
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ])->unique('id')->values();
        }

        return [
            // Identifiers
            'id' => $this->id,
            'slug' => $this->slug,

            // Core content
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'content' => $this->content,

            // Media
            'featured_image' => $this->featured_image_url,
            'featured_image_path' => $this->featured_image,
            'image_alt_text' => $this->image_alt_text,

            // Status
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'is_trending' => $this->is_trending,
            'view_count' => $this->view_count,

            // Timing
            'reading_time_minutes' => $this->reading_time,
            'reading_time_text' => $this->reading_time_text,
            'published_at' => $this->published_at?->toISOString(),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Author
            'author' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'avatar_url' => $this->user->avatar_url ?? null,
                ];
            }),

            // Categories
            'primary_category' => $primaryCategory,
            'categories' => $allCategories,

            // Tags (keywords)
            'tags' => $this->whenLoaded('tags', function () {
                return $this->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    ];
                });
            }),

            // Counts
            'comments_count' => $this->when($this->relationLoaded('comments'), fn () => $this->comments->count()),
            'bookmarks_count' => $this->when(isset($this->bookmarks_count), fn () => $this->bookmarks_count),

            // URLs
            'url' => route('post.show', $this->slug),

            // SEO
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'meta_tags' => $this->getMetaTags(),
            'structured_data' => $this->getStructuredData(),
        ];
    }
}
