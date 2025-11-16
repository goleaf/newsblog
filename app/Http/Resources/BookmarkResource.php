<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookmarkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'note' => $this->note,
            'order' => $this->order,
            'created_at' => $this->created_at?->toISOString(),
            'post' => $this->whenLoaded('post', function () {
                return [
                    'id' => $this->post->id,
                    'title' => $this->post->title,
                    'slug' => $this->post->slug,
                    'excerpt' => $this->post->excerpt,
                    'featured_image' => $this->post->featured_image_url,
                    'published_at' => $this->post->published_at?->toISOString(),
                ];
            }),
        ];
    }
}
