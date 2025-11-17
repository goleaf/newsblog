<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * This resource transforms comment data for API responses.
     * - Includes user information conditionally
     * - Includes reactions conditionally
     * - Formats threading structure with parent_id
     * - Formats dates consistently
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Identifiers
            'id' => $this->id,
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,

            // Content
            'content' => $this->content,
            'status' => $this->status?->value,

            // Author information
            'author_name' => $this->author_name,
            'author_email' => $this->when(
                $request->user()?->can('view', $this->resource),
                $this->author_email
            ),

            // User relationship - conditionally included
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),

            // Reactions - conditionally included
            'reactions' => $this->whenLoaded('reactions', function () use ($request) {
                $grouped = $this->reactions->groupBy('reaction_type')->map->count();

                return [
                    'like' => $grouped->get('like', 0),
                    'helpful' => $grouped->get('helpful', 0),
                    'insightful' => $grouped->get('insightful', 0),
                    'total' => $this->reactions->count(),
                    // Include user's reaction if authenticated
                    'user_reaction' => $this->when(
                        $request->user(),
                        function () use ($request) {
                            $userReaction = $this->reactions
                                ->where('user_id', $request->user()->id)
                                ->first();

                            return $userReaction?->reaction_type;
                        }
                    ),
                ];
            }),

            // Threading structure
            'depth' => $this->when(
                method_exists($this->resource, 'depth'),
                fn () => $this->depth()
            ),
            'can_reply' => $this->when(
                method_exists($this->resource, 'canReply'),
                fn () => $this->canReply()
            ),

            // Replies - conditionally included
            'replies' => $this->whenLoaded('replies', function () {
                return CommentResource::collection($this->replies);
            }),
            'replies_count' => $this->when(
                isset($this->replies_count),
                $this->replies_count
            ),

            // Timestamps - formatted consistently
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
