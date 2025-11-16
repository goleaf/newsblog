<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentReactionController extends Controller
{
    public function react(Request $request, int $commentId)
    {
        $request->validate([
            'type' => ['required', 'in:'.implode(',', CommentReaction::TYPES)],
        ]);

        $comment = Comment::findOrFail($commentId);

        CommentReaction::updateOrCreate(
            [
                'comment_id' => $comment->id,
                'user_id' => Auth::id(),
            ],
            [
                'type' => $request->type,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        $count = CommentReaction::where('comment_id', $comment->id)
            ->where('type', $request->type)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}

