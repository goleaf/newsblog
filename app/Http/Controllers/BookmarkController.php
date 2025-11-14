<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $query = Bookmark::with(['post.user', 'post.category'])
            ->where('bookmarks.user_id', Auth::id());

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('post', function ($q) use ($request) {
                $q->where('category_id', $request->category);
            });
        }

        // Sort options
        $sort = $request->get('sort', 'date_saved');
        switch ($sort) {
            case 'title':
                $query->join('posts', 'bookmarks.post_id', '=', 'posts.id')
                    ->orderBy('posts.title', 'asc')
                    ->select('bookmarks.*');
                break;
            case 'reading_time':
                $query->join('posts', 'bookmarks.post_id', '=', 'posts.id')
                    ->orderBy('posts.reading_time', 'asc')
                    ->select('bookmarks.*');
                break;
            case 'date_saved':
            default:
                $query->latest('bookmarks.created_at');
                break;
        }

        $bookmarks = $query->paginate(12)->withQueryString();

        // Get categories for filter
        $categories = \App\Models\Category::whereHas('posts', function ($q) {
            $q->whereIn('id', Bookmark::where('user_id', Auth::id())->pluck('post_id'));
        })->get();

        return view('bookmarks.index', compact('bookmarks', 'categories'));
    }

    public function toggle(Request $request, $postId)
    {
        $user = Auth::user();

        $bookmark = Bookmark::where('user_id', $user->id)
            ->where('post_id', $postId)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            $bookmarked = false;
            $message = __('post.remove_from_reading_list');
        } else {
            Bookmark::create([
                'user_id' => $user->id,
                'post_id' => $postId,
            ]);
            $bookmarked = true;
            $message = __('post.add_to_reading_list');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'bookmarked' => $bookmarked,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
