<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * This resource transforms article/post data for API responses.
     * - Includes relationships conditionally based on what's loaded
     * - Formats dates consistently using ISO 8601 format
     * - Conditionally includes full content only on detail views
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $primaryCategory = $this->whenLoaded('category', function () {
            return new CategoryResource($this->category);
        });

        // Merge primary category into all categories list for convenience
        $allCategories = collect();
        if ($this->relationLoaded('categories')) {
            $allCategories = CategoryResource::collection($this->categories);
        }
        if ($this->relationLoaded('category') && $this->category) {
            $allCategories = $allCategories->prepend(new CategoryResource($this->category))
                ->unique('id')
                ->values();
        }

        return [
            // Identifiers
            'id' => $this->id,
            'slug' => $this->slug,

            // Core content
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            // Only include full content on detail views (when showing single article)
            // Check if this is a show route by looking at route parameters
            'content' => $this->when(
                $request->route('slug') || $request->route('id') || $request->routeIs('*.show'),
                $this->content
            ),

            // Media
            'featured_image' => $this->featured_image_url,
            'image_alt_text' => $this->image_alt_text,

            // Status
            'status' => $this->status?->value,
            'is_featured' => $this->is_featured,
            'is_trending' => $this->is_trending,
            'view_count' => $this->view_count,

            // Timing - formatted consistently using ISO 8601
            'reading_time_minutes' => $this->reading_time,
            'reading_time_text' => $this->reading_time_text,
            'published_at' => $this->published_at?->toIso8601String(),
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Author - conditionally included
            'author' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),

            // Categories - conditionally included
            'category' => $primaryCategory,
            'categories' => $this->when($allCategories->isNotEmpty(), $allCategories),

            // Tags - conditionally included
            'tags' => $this->whenLoaded('tags', function () {
                return TagResource::collection($this->tags);
            }),

            // Counts
            'comments_count' => $this->when(
                $this->relationLoaded('comments'),
                fn () => $this->comments->count()
            ),
            'bookmarks_count' => $this->when(
                isset($this->bookmarks_count),
                fn () => $this->bookmarks_count
            ),

            // URLs
            'url' => route('post.show', $this->slug),

            // SEO - only on detail views
            'meta_title' => $this->when(
                $request->route('slug') || $request->route('id') || $request->routeIs('*.show'),
                $this->meta_title
            ),
            'meta_description' => $this->when(
                $request->route('slug') || $request->route('id') || $request->routeIs('*.show'),
                $this->meta_description
            ),
        ];
    }
}
