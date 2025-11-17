<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * This resource transforms tag data for API responses.
     * - Includes article counts when available
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

            // Counts
            'posts_count' => $this->when(
                isset($this->posts_count),
                fn () => $this->posts_count
            ),
            'articles_count' => $this->when(
                isset($this->posts_count),
                fn () => $this->posts_count
            ),

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
