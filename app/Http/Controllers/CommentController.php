<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'post_id' => ['required', 'exists:posts,id'],
            'author_name' => ['required', 'string', 'max:255'],
            'author_email' => ['required', 'email', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ]);

        // Basic spam protection: check for common spam patterns
        $spamKeywords = ['viagra', 'casino', 'loan', 'click here'];
        $contentLower = strtolower($validated['content']);
        
        $isSpam = false;
        foreach ($spamKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                $isSpam = true;
                break;
            }
        }

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

