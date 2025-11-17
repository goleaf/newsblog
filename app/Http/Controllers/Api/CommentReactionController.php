<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommentReactionController extends Controller
{
    /**
     * Store or toggle a reaction on a comment.
     */
    public function react(Request $request, int $commentId): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'in:'.implode(',', CommentReaction::TYPES)],
        ]);

        $comment = Comment::findOrFail($commentId);

        // Check if user already has this reaction
        $existingReaction = CommentReaction::where('comment_id', $comment->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingReaction) {
            // If same type, remove it (toggle off)
            if ($existingReaction->type === $request->type) {
                $existingReaction->delete();

                Log::info('Comment reaction removed', [
                    'comment_id' => $comment->id,
                    'user_id' => Auth::id(),
                    'type' => $request->type,
                ]);

                $counts = $this->getReactionCounts($comment);

                return response()->json([
                    'success' => true,
                    'action' => 'removed',
                    'counts' => $counts,
                    'count' => array_sum($counts),
                    'user_reaction' => null,
                ]);
            }

            // If different type, update it
            $existingReaction->update([
                'type' => $request->type,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            Log::info('Comment reaction updated', [
                'comment_id' => $comment->id,
                'user_id' => Auth::id(),
                'type' => $request->type,
            ]);

            $counts = $this->getReactionCounts($comment);

            return response()->json([
                'success' => true,
                'action' => 'updated',
                'counts' => $counts,
                'count' => array_sum($counts),
                'user_reaction' => $request->type,
            ]);
        }

        // Create new reaction
        CommentReaction::create([
            'comment_id' => $comment->id,
            'user_id' => Auth::id(),
            'type' => $request->type,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Log::info('Comment reaction added', [
            'comment_id' => $comment->id,
            'user_id' => Auth::id(),
            'type' => $request->type,
        ]);

        $counts = $this->getReactionCounts($comment);

        return response()->json([
            'success' => true,
            'action' => 'added',
            'counts' => $counts,
            'count' => array_sum($counts),
            'user_reaction' => $request->type,
        ]);
    }

    /**
     * Get reaction counts for a comment.
     */
    public function getCounts(int $commentId): JsonResponse
    {
        $comment = Comment::findOrFail($commentId);

        $userReaction = null;
        if (Auth::check()) {
            $reaction = CommentReaction::where('comment_id', $comment->id)
                ->where('user_id', Auth::id())
                ->first();
            $userReaction = $reaction?->type;
        }

        return response()->json([
            'counts' => $this->getReactionCounts($comment),
            'user_reaction' => $userReaction,
        ]);
    }

    /**
     * Get all reaction counts for a comment.
     */
    protected function getReactionCounts(Comment $comment): array
    {
        $counts = [];

        foreach (CommentReaction::TYPES as $type) {
            $counts[$type] = CommentReaction::where('comment_id', $comment->id)
                ->where('type', $type)
                ->count();
        }

        return $counts;
    }
}
