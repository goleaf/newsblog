<?php

namespace App\Http\Controllers;

use App\Http\Requests\Bookmarks\DestroyBookmarkRequest;
use App\Http\Requests\Bookmarks\IndexBookmarkRequest;
use App\Http\Requests\Bookmarks\StoreBookmarkRequest;
use App\Http\Requests\Bookmarks\ToggleBookmarkRequest;
use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookmarkController extends Controller
{
    protected function getOrCreateReaderToken(Request $request): string
    {
        $token = (string) $request->cookie('reader_token');
        if ($token === '') {
            $token = Str::uuid()->toString();
        }
        // 2 years
        cookie()->queue(cookie('reader_token', $token, 60 * 24 * 730, null, null, false, false, false, 'Lax'));

        return $token;
    }

    public function index(IndexBookmarkRequest $request): View
    {
        $readerToken = (string) $request->cookie('reader_token');

        $bookmarks = Bookmark::query()
            ->where('reader_token', $readerToken)
            ->with(['post' => ['category', 'tags', 'user']])
            ->latest()
            ->paginate(12);

        $collections = collect();
        $categories = collect();

        if (auth()->check()) {
            $collections = auth()->user()->bookmarkCollections()->withCount('bookmarks')->get();
        }

        return view('bookmarks.index', [
            'bookmarks' => $bookmarks,
            'collections' => $collections,
            'categories' => $categories,
        ]);
    }

    public function store(StoreBookmarkRequest $request): RedirectResponse|JsonResponse
    {
        $readerToken = $this->getOrCreateReaderToken($request);
        $postId = (int) $request->validated('post_id');

        $post = Post::query()->published()->findOrFail($postId);

        $bookmark = Bookmark::firstOrCreate([
            'reader_token' => $readerToken,
            'post_id' => $post->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'bookmarked' => true,
                'bookmark_id' => $bookmark->id,
                'count' => $post->bookmarks()->count(),
            ]);
        }

        return back()->with('status', __('Bookmarked'));
    }

    public function destroy(DestroyBookmarkRequest $request): RedirectResponse|JsonResponse
    {
        $readerToken = (string) $request->cookie('reader_token');
        $postId = (int) $request->validated('post_id');

        $post = Post::query()->findOrFail($postId);

        Bookmark::query()
            ->where('reader_token', $readerToken)
            ->where('post_id', $post->id)
            ->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'bookmarked' => false,
                'count' => $post->bookmarks()->count(),
            ]);
        }

        return back()->with('status', __('Bookmark removed'));
    }

    public function toggle(ToggleBookmarkRequest $request): JsonResponse
    {
        $readerToken = $this->getOrCreateReaderToken($request);
        $postId = (int) $request->validated('post_id');

        $post = Post::query()->published()->findOrFail($postId);

        $existing = Bookmark::query()
            ->where('reader_token', $readerToken)
            ->where('post_id', $post->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'ok' => true,
                'bookmarked' => false,
                'count' => $post->bookmarks()->count(),
            ]);
        }

        $bookmark = Bookmark::create([
            'reader_token' => $readerToken,
            'post_id' => $post->id,
        ]);

        return response()->json([
            'ok' => true,
            'bookmarked' => true,
            'bookmark_id' => $bookmark->id,
            'count' => $post->bookmarks()->count(),
        ]);
    }
}
