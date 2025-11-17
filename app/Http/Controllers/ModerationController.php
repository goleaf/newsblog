<?php

namespace App\Http\Controllers;

use App\Enums\CommentStatus;
use App\Enums\UserStatus;
use App\Models\Comment;
use App\Models\ModerationAction;
use App\Models\ModerationQueue;
use App\Models\User;
use App\Models\UserReputation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModerationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (! $request->user()->role->canModerate()) {
                abort(403, 'You do not have permission to access moderation features.');
            }

            return $next($request);
        });
    }

    /**
     * Display the moderation queue dashboard.
     */
    public function index(Request $request)
    {
        $query = ModerationQueue::with(['submitter', 'reviewer'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('reason')) {
            $query->where('reason', 'like', '%'.$request->reason.'%');
        }

        $queueItems = $query->paginate(20);

        // Get statistics
        $stats = [
            'pending' => ModerationQueue::where('status', 'pending')->count(),
            'approved' => ModerationQueue::where('status', 'approved')
                ->whereDate('reviewed_at', today())
                ->count(),
            'rejected' => ModerationQueue::where('status', 'rejected')
                ->whereDate('reviewed_at', today())
                ->count(),
        ];

        return view('moderation.index', compact('queueItems', 'stats'));
    }

    /**
     * Display the review interface for a specific item.
     */
    public function show(ModerationQueue $moderationQueue)
    {
        $moderationQueue->load(['submitter', 'reviewer']);

        // Load the actual content based on type
        $content = null;
        if ($moderationQueue->type === 'comment') {
            $content = Comment::with(['user', 'post', 'parent'])->find($moderationQueue->target_id);
        }

        // Get user history
        $userHistory = null;
        if ($content && $content->user) {
            $userHistory = $this->getUserModerationHistory($content->user);
        }

        return view('moderation.show', compact('moderationQueue', 'content', 'userHistory'));
    }

    /**
     * Approve a moderation queue item.
     */
    public function approve(Request $request, ModerationQueue $moderationQueue)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($moderationQueue, $request) {
            // Update moderation queue
            $moderationQueue->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'meta' => array_merge($moderationQueue->meta ?? [], [
                    'notes' => $request->notes,
                ]),
            ]);

            // Update the actual content
            if ($moderationQueue->type === 'comment') {
                $comment = Comment::find($moderationQueue->target_id);
                if ($comment) {
                    $comment->update(['status' => CommentStatus::Approved]);

                    // Update user reputation positively
                    $this->updateUserReputation($comment->user, 'approved');
                }
            }

            // Log moderation action
            ModerationAction::create([
                'moderator_id' => auth()->id(),
                'action_type' => 'approve',
                'subject_type' => $moderationQueue->type,
                'subject_id' => $moderationQueue->target_id,
                'reason' => $request->notes,
            ]);

            Log::info('Content approved by moderator', [
                'moderator_id' => auth()->id(),
                'queue_id' => $moderationQueue->id,
                'type' => $moderationQueue->type,
                'target_id' => $moderationQueue->target_id,
            ]);
        });

        return redirect()->route('moderation.index')
            ->with('success', 'Content approved successfully.');
    }

    /**
     * Reject a moderation queue item.
     */
    public function reject(Request $request, ModerationQueue $moderationQueue)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($moderationQueue, $request) {
            // Update moderation queue
            $moderationQueue->update([
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'meta' => array_merge($moderationQueue->meta ?? [], [
                    'rejection_reason' => $request->reason,
                ]),
            ]);

            // Update the actual content
            if ($moderationQueue->type === 'comment') {
                $comment = Comment::find($moderationQueue->target_id);
                if ($comment) {
                    $comment->update(['status' => CommentStatus::Rejected]);

                    // Update user reputation negatively
                    $this->updateUserReputation($comment->user, 'rejected');
                }
            }

            // Log moderation action
            ModerationAction::create([
                'moderator_id' => auth()->id(),
                'action_type' => 'reject',
                'subject_type' => $moderationQueue->type,
                'subject_id' => $moderationQueue->target_id,
                'reason' => $request->reason,
            ]);

            Log::channel('security')->info('Content rejected by moderator', [
                'moderator_id' => auth()->id(),
                'queue_id' => $moderationQueue->id,
                'type' => $moderationQueue->type,
                'target_id' => $moderationQueue->target_id,
                'reason' => $request->reason,
            ]);
        });

        return redirect()->route('moderation.index')
            ->with('success', 'Content rejected successfully.');
    }

    /**
     * Delete a moderation queue item and its content.
     */
    public function delete(Request $request, ModerationQueue $moderationQueue)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($moderationQueue, $request) {
            // Delete the actual content
            if ($moderationQueue->type === 'comment') {
                $comment = Comment::find($moderationQueue->target_id);
                if ($comment) {
                    // Update user reputation negatively
                    $this->updateUserReputation($comment->user, 'deleted');

                    $comment->delete();
                }
            }

            // Update moderation queue
            $moderationQueue->update([
                'status' => 'deleted',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'meta' => array_merge($moderationQueue->meta ?? [], [
                    'deletion_reason' => $request->reason,
                ]),
            ]);

            // Log moderation action
            ModerationAction::create([
                'moderator_id' => auth()->id(),
                'action_type' => 'delete',
                'subject_type' => $moderationQueue->type,
                'subject_id' => $moderationQueue->target_id,
                'reason' => $request->reason,
            ]);

            Log::channel('security')->warning('Content deleted by moderator', [
                'moderator_id' => auth()->id(),
                'queue_id' => $moderationQueue->id,
                'type' => $moderationQueue->type,
                'target_id' => $moderationQueue->target_id,
                'reason' => $request->reason,
            ]);
        });

        return redirect()->route('moderation.index')
            ->with('success', 'Content deleted successfully.');
    }

    /**
     * Perform bulk moderation actions.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,delete',
            'items' => 'required|array|min:1',
            'items.*' => 'exists:moderation_queues,id',
            'reason' => 'required_if:action,reject,delete|string|max:1000',
        ]);

        $items = ModerationQueue::whereIn('id', $request->items)->get();
        $count = 0;

        DB::transaction(function () use ($items, $request, &$count) {
            foreach ($items as $item) {
                switch ($request->action) {
                    case 'approve':
                        $this->performApprove($item);
                        $count++;
                        break;
                    case 'reject':
                        $this->performReject($item, $request->reason);
                        $count++;
                        break;
                    case 'delete':
                        $this->performDelete($item, $request->reason);
                        $count++;
                        break;
                }
            }
        });

        return redirect()->route('moderation.index')
            ->with('success', "Bulk action completed: {$count} items processed.");
    }

    /**
     * Ban a user.
     */
    public function banUser(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
            'duration' => 'nullable|in:temporary,permanent',
            'days' => 'required_if:duration,temporary|integer|min:1|max:365',
        ]);

        DB::transaction(function () use ($user, $request) {
            // Update user status
            $user->update(['status' => UserStatus::Suspended]);

            // Log ban action
            ModerationAction::create([
                'moderator_id' => auth()->id(),
                'action_type' => 'ban_user',
                'subject_type' => 'user',
                'subject_id' => $user->id,
                'reason' => $request->reason,
            ]);

            // Update user reputation
            $reputation = UserReputation::firstOrCreate(
                ['user_id' => $user->id],
                ['score' => 0, 'level' => 'new']
            );

            $reputation->update([
                'score' => max(0, $reputation->score - 50),
                'level' => 'banned',
            ]);

            Log::channel('security')->warning('User banned', [
                'moderator_id' => auth()->id(),
                'user_id' => $user->id,
                'reason' => $request->reason,
                'duration' => $request->duration,
            ]);
        });

        return redirect()->back()
            ->with('success', 'User has been banned successfully.');
    }

    /**
     * Get user moderation history.
     */
    protected function getUserModerationHistory(User $user): array
    {
        $reputation = UserReputation::where('user_id', $user->id)->first();

        $comments = Comment::where('user_id', $user->id)->get();

        return [
            'reputation' => $reputation,
            'total_comments' => $comments->count(),
            'approved_comments' => $comments->where('status', CommentStatus::Approved)->count(),
            'rejected_comments' => $comments->where('status', CommentStatus::Rejected)->count(),
            'flagged_comments' => $comments->where('status', CommentStatus::Flagged)->count(),
            'recent_actions' => ModerationAction::where('subject_type', 'comment')
                ->whereIn('subject_id', $comments->pluck('id'))
                ->with('moderator')
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Update user reputation based on moderation action.
     */
    protected function updateUserReputation(User $user, string $action): void
    {
        $reputation = UserReputation::firstOrCreate(
            ['user_id' => $user->id],
            ['score' => 0, 'level' => 'new', 'meta' => []]
        );

        $scoreChange = match ($action) {
            'approved' => 5,
            'rejected' => -10,
            'deleted' => -20,
            default => 0,
        };

        $newScore = max(0, $reputation->score + $scoreChange);

        // Determine level based on score
        $level = match (true) {
            $newScore >= 100 => 'expert',
            $newScore >= 50 => 'trusted',
            $newScore >= 20 => 'member',
            default => 'new',
        };

        $reputation->update([
            'score' => $newScore,
            'level' => $level,
        ]);
    }

    /**
     * Perform approve action (helper for bulk operations).
     */
    protected function performApprove(ModerationQueue $item): void
    {
        $item->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        if ($item->type === 'comment') {
            $comment = Comment::find($item->target_id);
            if ($comment) {
                $comment->update(['status' => CommentStatus::Approved]);
                $this->updateUserReputation($comment->user, 'approved');
            }
        }
    }

    /**
     * Perform reject action (helper for bulk operations).
     */
    protected function performReject(ModerationQueue $item, string $reason): void
    {
        $item->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'meta' => array_merge($item->meta ?? [], ['rejection_reason' => $reason]),
        ]);

        if ($item->type === 'comment') {
            $comment = Comment::find($item->target_id);
            if ($comment) {
                $comment->update(['status' => CommentStatus::Rejected]);
                $this->updateUserReputation($comment->user, 'rejected');
            }
        }
    }

    /**
     * Perform delete action (helper for bulk operations).
     */
    protected function performDelete(ModerationQueue $item, string $reason): void
    {
        if ($item->type === 'comment') {
            $comment = Comment::find($item->target_id);
            if ($comment) {
                $this->updateUserReputation($comment->user, 'deleted');
                $comment->delete();
            }
        }

        $item->update([
            'status' => 'deleted',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'meta' => array_merge($item->meta ?? [], ['deletion_reason' => $reason]),
        ]);
    }
}
