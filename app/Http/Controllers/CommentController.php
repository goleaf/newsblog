<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Jobs\SendCommentReplyNotification;
use App\Models\Comment;
use App\Services\SpamDetectionService;

class CommentController extends Controller
{
    public function __construct(private SpamDetectionService $spamDetectionService) {}

    public function store(StoreCommentRequest $request)
    {
        $validated = $request->validated();

        // Calculate time on page
        $timeOnPage = null;
        if (isset($validated['page_load_time'])) {
            $timeOnPage = time() - $validated['page_load_time'];
        }

        // Check for spam using SpamDetectionService
        $context = [
            'time_on_page' => $timeOnPage,
            'honeypot' => $validated['honeypot'] ?? null,
        ];

        $isSpam = $this->spamDetectionService->isSpam($validated['content'], $context);

        $comment = Comment::create([
            'post_id' => $validated['post_id'],
            'author_name' => $validated['author_name'],
            'author_email' => $validated['author_email'],
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'status' => $isSpam ? 'spam' : 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($isSpam) {
            return redirect()->back()->with('error', 'Your comment has been flagged as spam.');
        }

        // If this is a reply and not spam, queue notification to parent commenter (Requirement 23.4)
        if ($comment->parent_id && ! $isSpam) {
            dispatch(new SendCommentReplyNotification($comment));
        }

        $message = $comment->parent_id
            ? 'Your reply has been submitted and is pending approval.'
            : 'Your comment has been submitted and is pending approval.';

        return redirect()->back()->with('success', $message);
    }
}
