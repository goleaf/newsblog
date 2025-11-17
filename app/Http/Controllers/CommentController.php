<?php

namespace App\Http\Controllers;

use App\Enums\CommentStatus;
use App\Http\Requests\ApproveCommentRequest;
use App\Http\Requests\DestroyCommentRequest;
use App\Http\Requests\RejectCommentRequest;
use App\Http\Requests\ReplyCommentRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Jobs\SendCommentApprovedNotification;
use App\Jobs\SendCommentReplyNotification;
use App\Models\Comment;
use App\Services\ContentSanitizer;
use App\Services\SpamDetectionService;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function __construct(
        private SpamDetectionService $spamDetectionService,
        private ContentSanitizer $contentSanitizer
    ) {}

    /**
     * Store a new comment.
     */
    public function store(StoreCommentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Sanitize user-provided HTML content
        $validated['content'] = $this->contentSanitizer->sanitizeComment($validated['content']);

        // Calculate time on page
        $timeOnPage = null;
        if (isset($validated['page_load_time'])) {
            $timeOnPage = time() - $validated['page_load_time'];
        }

        $ipAddress = $request->ip();

        // Check for spam using SpamDetectionService
        $context = [
            'time_on_page' => $timeOnPage,
            'honeypot' => $validated['honeypot'] ?? null,
            'ip' => $ipAddress,
        ];

        $isSpam = $this->spamDetectionService->isSpam($validated['content'], $context);

        // Check if IP is blocked
        $isBlocked = $this->spamDetectionService->blockIp($ipAddress);

        if ($isBlocked) {
            return redirect()->back()->with('error', 'Your IP address has been temporarily blocked due to suspicious activity.');
        }

        $comment = Comment::create([
            'post_id' => $validated['post_id'],
            'author_name' => $validated['author_name'],
            'author_email' => $validated['author_email'],
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'status' => $isSpam ? CommentStatus::Spam : CommentStatus::Pending,
            'ip_address' => $ipAddress,
            'user_agent' => $request->userAgent(),
        ]);

        if ($isSpam) {
            return redirect()->back()->with('error', 'Your comment has been flagged as spam.');
        }

        // If this is a reply and not spam, queue notification to parent commenter
        if ($comment->parent_id && ! $isSpam) {
            dispatch(new SendCommentReplyNotification($comment));
        }

        $message = $comment->parent_id
            ? 'Your reply has been submitted and is pending approval.'
            : 'Your comment has been submitted and is pending approval.';

        return redirect()->back()->with('success', $message);
    }

    /**
     * Store a reply to an existing comment.
     */
    public function reply(ReplyCommentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Get parent comment to extract post_id
        $parentComment = Comment::findOrFail($validated['parent_id']);

        // Check if parent comment can receive replies (max 3 levels)
        if (! $parentComment->canReply()) {
            return redirect()->back()->with('error', 'Maximum nesting level reached. Cannot reply to this comment.');
        }

        // Calculate time on page
        $timeOnPage = null;
        if (isset($validated['page_load_time'])) {
            $timeOnPage = time() - $validated['page_load_time'];
        }

        $ipAddress = $request->ip();

        // Sanitize user-provided HTML content
        $validated['content'] = $this->contentSanitizer->sanitizeComment($validated['content']);

        // Check for spam using SpamDetectionService
        $context = [
            'time_on_page' => $timeOnPage,
            'honeypot' => $validated['honeypot'] ?? null,
            'ip' => $ipAddress,
        ];

        $isSpam = $this->spamDetectionService->isSpam($validated['content'], $context);

        // Check if IP is blocked
        $isBlocked = $this->spamDetectionService->blockIp($ipAddress);

        if ($isBlocked) {
            return redirect()->back()->with('error', 'Your IP address has been temporarily blocked due to suspicious activity.');
        }

        $comment = Comment::create([
            'post_id' => $parentComment->post_id,
            'parent_id' => $validated['parent_id'],
            'author_name' => $validated['author_name'],
            'author_email' => $validated['author_email'],
            'content' => $validated['content'],
            'status' => $isSpam ? CommentStatus::Spam : CommentStatus::Pending,
            'ip_address' => $ipAddress,
            'user_agent' => $request->userAgent(),
        ]);

        if ($isSpam) {
            return redirect()->back()->with('error', 'Your reply has been flagged as spam.');
        }

        // Queue notification to parent commenter
        dispatch(new SendCommentReplyNotification($comment));

        return redirect()->back()->with('success', 'Your reply has been submitted and is pending approval.');
    }

    /**
     * Approve a comment (moderation).
     */
    public function approve(ApproveCommentRequest $request, Comment $comment): RedirectResponse
    {
        if ($comment->status === CommentStatus::Approved) {
            return redirect()->back()->with('info', 'Comment is already approved.');
        }

        $comment->markAsApproved();

        // Queue notification to comment author
        dispatch(new SendCommentApprovedNotification($comment));

        return redirect()->back()->with('success', 'Comment approved successfully.');
    }

    /**
     * Reject a comment (moderation).
     */
    public function reject(RejectCommentRequest $request, Comment $comment): RedirectResponse
    {
        if ($comment->status === CommentStatus::Rejected) {
            return redirect()->back()->with('info', 'Comment is already rejected.');
        }

        $comment->markAsRejected();

        return redirect()->back()->with('success', 'Comment rejected successfully.');
    }

    /**
     * Delete a comment.
     */
    public function destroy(DestroyCommentRequest $request, Comment $comment): RedirectResponse
    {
        $comment->delete();

        return redirect()->back()->with('success', 'Comment deleted successfully.');
    }
}
