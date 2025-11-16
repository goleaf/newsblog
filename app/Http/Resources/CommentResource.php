<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,
            'author_name' => $this->author_name,
            'author_email' => $this->when($request->user()?->can('view', $this->resource), $this->author_email),
            'content' => $this->content,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'replies_count' => $this->when(isset($this->replies_count), $this->replies_count),
        ];
    }
}
