<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Services\SpamDetectionService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(private SpamDetectionService $spamDetectionService) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'post_id' => ['required', 'exists:posts,id'],
            'author_name' => ['required', 'string', 'max:255'],
            'author_email' => ['required', 'email', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
            'honeypot' => ['nullable'], // Honeypot field for bot detection
            'page_load_time' => ['nullable', 'numeric'], // Time when page was loaded
        ]);

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

        return redirect()->back()->with('success', 'Your comment has been submitted and is pending approval.');
    }
}
