<?php

namespace App\Services;

use App\Enums\CommentStatus;
use App\Jobs\SendCommentReplyNotification;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Support\Html\SimpleSanitizer;
use Illuminate\Support\Facades\Log;

class CommentService
{
    public function __construct(
        private SpamDetectionService $spamDetectionService
    ) {}

    /**
     * Create a new comment with auto-moderation check.
     */
    public function create(array $data, ?User $user = null, ?string $ipAddress = null, ?string $userAgent = null): Comment
    {
        // Sanitize content
        $data['content'] = SimpleSanitizer::sanitize($data['content']);

        // Calculate time on page if provided
        $timeOnPage = null;
        if (isset($data['page_load_time'])) {
            $timeOnPage = time() - $data['page_load_time'];
        }

        // Check for spam
        $context = [
            'time_on_page' => $timeOnPage,
            'honeypot' => $data['honeypot'] ?? null,
            'ip' => $ipAddress,
        ];

        $isSpam = $this->spamDetectionService->isSpam($data['content'], $context);

        // Check if IP is blocked
        if ($ipAddress && $this->spamDetectionService->blockIp($ipAddress)) {
            throw new \Exception('Your IP address has been temporarily blocked due to suspicious activity.');
        }

        // Determine initial status
        $status = $isSpam ? CommentStatus::Spam : CommentStatus::Pending;

        // Create comment
        $comment = Comment::create([
            'post_id' => $data['post_id'],
            'user_id' => $user?->id,
            'parent_id' => $data['parent_id'] ?? null,
            'author_name' => $data['author_name'],
            'author_email' => $data['author_email'],
            'content' => $data['content'],
            'status' => $status,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        // Log comment creation
        Log::info('Comment created', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
            'status' => $status->value,
            'is_spam' => $isSpam,
        ]);

        // Notify parent comment author on reply (if not spam)
        if ($comment->parent_id && ! $isSpam) {
            $this->notifyParentCommentAuthor($comment);
        }

        return $comment;
    }

    /**
     * Update an existing comment.
     */
    public function update(Comment $comment, array $data): Comment
    {
        // Sanitize content
        $data['content'] = SimpleSanitizer::sanitize($data['content']);

        // Update comment
        $comment->update([
            'content' => $data['content'],
        ]);

        Log::info('Comment updated', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
        ]);

        return $comment->fresh();
    }

    /**
     * Delete a comment (soft delete).
     */
    public function delete(Comment $comment): bool
    {
        Log::info('Comment deleted', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
        ]);

        return $comment->delete();
    }

    /**
     * Notify parent comment author on reply.
     */
    protected function notifyParentCommentAuthor(Comment $comment): void
    {
        if (! $comment->parent) {
            return;
        }

        dispatch(new SendCommentReplyNotification($comment));

        Log::info('Comment reply notification queued', [
            'comment_id' => $comment->id,
            'parent_comment_id' => $comment->parent_id,
        ]);
    }

    /**
     * Approve a comment.
     */
    public function approve(Comment $comment): Comment
    {
        if ($comment->status === CommentStatus::Approved) {
            return $comment;
        }

        $comment->markAsApproved();

        Log::info('Comment approved', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
        ]);

        return $comment->fresh();
    }

    /**
     * Reject a comment.
     */
    public function reject(Comment $comment): Comment
    {
        if ($comment->status === CommentStatus::Rejected) {
            return $comment;
        }

        $comment->markAsRejected();

        Log::info('Comment rejected', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
        ]);

        return $comment->fresh();
    }

    /**
     * Mark a comment as spam.
     */
    public function markAsSpam(Comment $comment): Comment
    {
        $comment->markAsSpam();

        Log::info('Comment marked as spam', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
        ]);

        return $comment->fresh();
    }

    /**
     * Get comments for a post with threading.
     */
    public function getCommentsForPost(Post $post, bool $approvedOnly = true)
    {
        $query = Comment::where('post_id', $post->id)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user', 'replies.reactions', 'reactions']);

        if ($approvedOnly) {
            $query->approved();
        }

        return $query->latest()->get();
    }

    /**
     * Get comment count for a post.
     */
    public function getCommentCount(Post $post, bool $approvedOnly = true): int
    {
        $query = Comment::where('post_id', $post->id);

        if ($approvedOnly) {
            $query->approved();
        }

        return $query->count();
    }
}
