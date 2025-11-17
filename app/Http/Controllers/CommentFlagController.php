<?php

namespace App\Http\Controllers;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\CommentFlag;
use App\Models\ModerationQueue;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CommentFlagController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Flag a comment for moderation.
     */
    public function store(Request $request, Comment $comment)
    {
        $request->validate([
            'reason' => 'required|in:spam,offensive,off-topic',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if user has already flagged this comment
        $existingFlag = CommentFlag::where('comment_id', $comment->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingFlag) {
            return back()->with('error', 'You have already flagged this comment.');
        }

        DB::transaction(function () use ($comment, $request) {
            // Create flag
            $flag = CommentFlag::create([
                'comment_id' => $comment->id,
                'user_id' => auth()->id(),
                'reason' => $request->reason,
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            // Count total flags for this comment
            $flagCount = CommentFlag::where('comment_id', $comment->id)
                ->where('status', 'pending')
                ->count();

            // Auto-flag comment if it has multiple flags
            if ($flagCount >= 3 && $comment->status !== CommentStatus::Flagged) {
                $comment->update(['status' => CommentStatus::Flagged]);

                // Add to moderation queue
                $this->addToModerationQueue($comment, 'Multiple user reports');
            }

            // Notify moderators
            $this->notifyModerators($comment, $flag);

            Log::info('Comment flagged by user', [
                'comment_id' => $comment->id,
                'user_id' => auth()->id(),
                'reason' => $request->reason,
                'flag_count' => $flagCount,
            ]);
        });

        return back()->with('success', 'Comment has been flagged for review. Thank you for helping maintain our community standards.');
    }

    /**
     * Get flags for a comment (moderators only).
     */
    public function index(Comment $comment)
    {
        if (! auth()->user()->role->canModerate()) {
            abort(403);
        }

        $flags = CommentFlag::where('comment_id', $comment->id)
            ->with('user')
            ->latest()
            ->get();

        return view('moderation.flags', compact('comment', 'flags'));
    }

    /**
     * Dismiss a flag (moderators only).
     */
    public function dismiss(CommentFlag $flag)
    {
        if (! auth()->user()->role->canModerate()) {
            abort(403);
        }

        $flag->update(['status' => 'dismissed']);

        Log::info('Comment flag dismissed', [
            'flag_id' => $flag->id,
            'comment_id' => $flag->comment_id,
            'moderator_id' => auth()->id(),
        ]);

        return back()->with('success', 'Flag has been dismissed.');
    }

    /**
     * Resolve a flag (moderators only).
     */
    public function resolve(CommentFlag $flag)
    {
        if (! auth()->user()->role->canModerate()) {
            abort(403);
        }

        $flag->update(['status' => 'resolved']);

        Log::info('Comment flag resolved', [
            'flag_id' => $flag->id,
            'comment_id' => $flag->comment_id,
            'moderator_id' => auth()->id(),
        ]);

        return back()->with('success', 'Flag has been resolved.');
    }

    /**
     * Add comment to moderation queue.
     */
    protected function addToModerationQueue(Comment $comment, string $reason): void
    {
        // Check if already in queue
        $existingQueue = ModerationQueue::where('type', 'comment')
            ->where('target_id', $comment->id)
            ->where('status', 'pending')
            ->first();

        if ($existingQueue) {
            return;
        }

        ModerationQueue::create([
            'type' => 'comment',
            'target_id' => $comment->id,
            'status' => 'pending',
            'reason' => $reason,
            'submitted_by' => null, // System-generated
            'meta' => [
                'auto_flagged' => true,
                'flag_count' => CommentFlag::where('comment_id', $comment->id)->count(),
            ],
        ]);
    }

    /**
     * Notify moderators about flagged content.
     */
    protected function notifyModerators(Comment $comment, CommentFlag $flag): void
    {
        // Get all moderators
        $moderators = User::where('role', \App\Enums\UserRole::Moderator)
            ->orWhere('role', \App\Enums\UserRole::Admin)
            ->get();

        // Count flags for this comment
        $flagCount = CommentFlag::where('comment_id', $comment->id)
            ->where('status', 'pending')
            ->count();

        // Only notify if this is the first flag or every 3rd flag
        if ($flagCount === 1 || $flagCount % 3 === 0) {
            foreach ($moderators as $moderator) {
                // Create notification
                \App\Models\Notification::create([
                    'user_id' => $moderator->id,
                    'type' => 'comment_flagged',
                    'data' => [
                        'comment_id' => $comment->id,
                        'post_id' => $comment->post_id,
                        'post_title' => $comment->post->title,
                        'flag_reason' => $flag->reason,
                        'flag_count' => $flagCount,
                        'flagged_by' => auth()->user()->name,
                    ],
                    'read_at' => null,
                ]);
            }
        }
    }

    /**
     * Get flagging statistics (moderators only).
     */
    public function statistics()
    {
        if (! auth()->user()->role->canModerate()) {
            abort(403);
        }

        $stats = [
            'total_flags' => CommentFlag::count(),
            'pending_flags' => CommentFlag::where('status', 'pending')->count(),
            'resolved_flags' => CommentFlag::where('status', 'resolved')->count(),
            'dismissed_flags' => CommentFlag::where('status', 'dismissed')->count(),
            'by_reason' => [
                'spam' => CommentFlag::where('reason', 'spam')->count(),
                'offensive' => CommentFlag::where('reason', 'offensive')->count(),
                'off-topic' => CommentFlag::where('reason', 'off-topic')->count(),
            ],
            'top_flaggers' => CommentFlag::select('user_id', DB::raw('count(*) as flag_count'))
                ->groupBy('user_id')
                ->orderByDesc('flag_count')
                ->limit(10)
                ->with('user')
                ->get(),
            'most_flagged_comments' => CommentFlag::select('comment_id', DB::raw('count(*) as flag_count'))
                ->groupBy('comment_id')
                ->orderByDesc('flag_count')
                ->limit(10)
                ->with('comment.user', 'comment.post')
                ->get(),
        ];

        return view('moderation.flag-statistics', compact('stats'));
    }
}
