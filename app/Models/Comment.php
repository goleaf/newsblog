<?php

namespace App\Models;

use App\Enums\CommentStatus as CommentStatusEnum;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'author_name',
        'author_email',
        'content',
        'status',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CommentStatusEnum::class,
        ];
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')->where('status', CommentStatusEnum::Approved);
    }

    /**
     * Reactions on this comment.
     */
    public function reactions()
    {
        return $this->hasMany(CommentReaction::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', CommentStatusEnum::Approved);
    }

    public function scopePending($query)
    {
        return $query->where('status', CommentStatusEnum::Pending);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', CommentStatusEnum::Rejected);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForPost($query, $postId)
    {
        return $query->where('post_id', $postId);
    }

    public function isApproved(): bool
    {
        return $this->status === CommentStatusEnum::Approved;
    }

    public function markAsApproved(): void
    {
        $this->update(['status' => CommentStatusEnum::Approved]);
    }

    public function markAsSpam(): void
    {
        $this->update(['status' => CommentStatusEnum::Spam]);
    }

    public function markAsRejected(): void
    {
        $this->update(['status' => CommentStatusEnum::Rejected]);
    }

    /**
     * Check if the comment can receive replies (max 3 levels of nesting)
     * Level 1 (depth 0) can reply -> creates Level 2 (depth 1)
     * Level 2 (depth 1) can reply -> creates Level 3 (depth 2)
     * Level 3 (depth 2) cannot reply (would create depth 3, which exceeds limit)
     */
    public function canReply(): bool
    {
        return $this->depth() < 2;
    }

    /**
     * Calculate the depth of this comment in the nesting hierarchy
     */
    public function depth(): int
    {
        $depth = 0;
        $comment = $this;

        while ($comment->parent) {
            $depth++;
            $comment = $comment->parent;
        }

        return $depth;
    }

    /**
     * Get all replies recursively
     */
    public function allReplies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
}
