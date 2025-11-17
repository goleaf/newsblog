<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * This resource transforms category data for API responses.
     * - Includes article counts when available
     * - Includes parent/child relationships conditionally
     * - Formats dates consistently
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Identifiers
            'id' => $this->id,
            'slug' => $this->slug,

            // Basic information
            'name' => $this->name,
            'description' => $this->description,

            // Hierarchy
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function () {
                return new CategoryResource($this->parent);
            }),
            'children' => $this->whenLoaded('children', function () {
                return CategoryResource::collection($this->children);
            }),

            // Styling
            'icon' => $this->icon,
            'color_code' => $this->color_code,

            // Counts
            'posts_count' => $this->when(
                isset($this->posts_count),
                fn () => $this->posts_count
            ),
            'articles_count' => $this->when(
                isset($this->posts_count),
                fn () => $this->posts_count
            ),

            // SEO
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,

            // URLs
            'url' => $this->when(
                method_exists($this->resource, 'getUrlAttribute'),
                fn () => $this->url
            ),

            // Timestamps - formatted consistently
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
