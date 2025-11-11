<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Bookmark;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostInteractionController extends Controller
{
    public function react(Request $request, $postId)
    {
        $request->validate([
            'type' => ['required', 'in:' . implode(',', Reaction::TYPES)],
        ]);

        $post = Post::findOrFail($postId);

        $reaction = Reaction::updateOrCreate(
            [
                'post_id' => $post->id,
                'user_id' => Auth::id(),
            ],
            [
                'type' => $request->type,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        $count = Reaction::where('post_id', $post->id)
            ->where('type', $request->type)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    public function bookmark(Request $request, $postId)
    {
        $post = Post::findOrFail($postId);
        $user = Auth::user();

        $bookmark = Bookmark::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            $bookmarked = false;
        } else {
            Bookmark::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
            $bookmarked = true;
        }

        return response()->json([
            'success' => true,
            'bookmarked' => $bookmarked,
        ]);
    }
}

