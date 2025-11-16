<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentFlagController extends Controller
{
    public function store(Request $request, int $commentId)
    {
        $request->validate([
            'reason' => ['required', 'in:'.implode(',', CommentFlag::REASONS)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $comment = Comment::findOrFail($commentId);

        $flag = CommentFlag::create([
            'comment_id' => $comment->id,
            'user_id' => Auth::id(),
            'reason' => $request->reason,
            'notes' => $request->notes,
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'id' => $flag->id,
            'status' => $flag->status,
        ], 201);
    }
}
