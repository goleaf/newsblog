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
    protected function readReaderToken(Request $request): string
    {
        $cookie = $request->cookie('reader_token');
        $token = is_string($cookie) ? $cookie : '';
        if ($token !== '') {
            return $token;
        }

        // Allow explicit token via request input or header when cookies are unavailable (e.g., certain test clients)
        $inputToken = (string) $request->input('reader_token', '');
        if ($inputToken !== '') {
            return $inputToken;
        }

        $headerToken = (string) $request->header('X-Reader-Token', '');
        if ($headerToken !== '') {
            return $headerToken;
        }

        // Fallback: parse the raw Cookie header (test clients sometimes bypass cookie bag)
        $raw = (string) $request->server('HTTP_COOKIE', '');
        foreach (explode(';', $raw) as $part) {
            [$name, $value] = array_map('trim', explode('=', $part, 2) + [null, null]);
            if ($name === 'reader_token' && is_string($value) && $value !== '') {
                return $value;
            }
        }

        // Final fallback: PHP superglobal (in some test environments)
        if (isset($_COOKIE['reader_token']) && is_string($_COOKIE['reader_token'])) {
            return (string) $_COOKIE['reader_token'];
        }

        return '';
    }

    protected function getOrCreateReaderToken(Request $request): string
    {
        $token = $this->readReaderToken($request);
        if ($token === '') {
            $token = Str::uuid()->toString();
        }
        // 2 years
        cookie()->queue(cookie('reader_token', $token, 60 * 24 * 730, null, null, false, false, false, 'Lax'));

        return $token;
    }

    public function index(IndexBookmarkRequest $request): View
    {
        $user = $request->user();
        $readerToken = $this->readReaderToken($request);

        $query = Bookmark::query()
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->when(! $user, fn ($q) => $q->where('reader_token', $readerToken))
            ->with(['post' => ['category', 'tags', 'user']]);

        // Filter by read status
        if ($request->has('status')) {
            if ($request->input('status') === 'read') {
                $query->where('is_read', true);
            } elseif ($request->input('status') === 'unread') {
                $query->where('is_read', false);
            }
        }

        // Sort bookmarks
        $sort = $request->input('sort', 'date_saved');
        switch ($sort) {
            case 'title':
                $query->join('posts', 'bookmarks.post_id', '=', 'posts.id')
                    ->orderBy('posts.title')
                    ->select('bookmarks.*');
                break;
            case 'reading_time':
                $query->join('posts', 'bookmarks.post_id', '=', 'posts.id')
                    ->orderBy('posts.reading_time')
                    ->select('bookmarks.*');
                break;
            default:
                $query->latest();
                break;
        }

        $bookmarks = $query->paginate(12)->withQueryString();

        $collections = $user ? $user->bookmarkCollections()->withCount('bookmarks')->get() : collect();
        $categories = collect();

        return view('bookmarks.index', [
            'bookmarks' => $bookmarks,
            'collections' => $collections,
            'categories' => $categories,
        ]);
    }

    public function store(StoreBookmarkRequest $request, Post $post): RedirectResponse|JsonResponse
    {
        $userId = $request->user()->id;
        abort_unless($post->status->value === 'published', 404);

        $bookmark = Bookmark::firstOrCreate([
            'user_id' => $userId,
            'post_id' => $post->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'ok' => true,
                'bookmarked' => true,
                'bookmark_id' => $bookmark->id,
                'count' => $post->bookmarks()->count(),
            ], 201);
        }

        return back()->with('status', __('Bookmarked'));
    }

    public function destroy(DestroyBookmarkRequest $request, Post $post): RedirectResponse|JsonResponse
    {
        $userId = $request->user()->id;

        $deleted = Bookmark::query()
            ->where('user_id', $userId)
            ->where('post_id', $post->id)
            ->delete();

        if ($deleted === 0) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                ], 403);
            }

            abort(403);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'ok' => true,
                'bookmarked' => false,
                'count' => $post->bookmarks()->count(),
            ]);
        }

        return back()->with('status', __('Bookmark removed'));
    }

    public function toggle(ToggleBookmarkRequest $request, Post $post): JsonResponse
    {
        $userId = $request->user()->id;
        abort_unless($post->status->value === 'published', 404);

        $existing = Bookmark::query()
            ->where('user_id', $userId)
            ->where('post_id', $post->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'success' => true,
                'ok' => true,
                'bookmarked' => false,
                'count' => $post->bookmarks()->count(),
            ]);
        }

        $bookmark = Bookmark::create([
            'user_id' => $userId,
            'post_id' => $post->id,
        ]);

        return response()->json([
            'success' => true,
            'ok' => true,
            'bookmarked' => true,
            'bookmark_id' => $bookmark->id,
            'count' => $post->bookmarks()->count(),
        ]);
    }

    public function markAsRead(Request $request, Bookmark $bookmark): JsonResponse
    {
        $readerToken = $this->readReaderToken($request);

        if (! $readerToken || $bookmark->reader_token !== $readerToken) {
            abort(403, 'Unauthorized');
        }

        $bookmark->markAsRead();

        return response()->json([
            'ok' => true,
            'is_read' => true,
            'read_at' => $bookmark->read_at?->toIso8601String(),
        ]);
    }

    public function markAsUnread(Request $request, Bookmark $bookmark): JsonResponse
    {
        $readerToken = $this->readReaderToken($request);

        if (! $readerToken || $bookmark->reader_token !== $readerToken) {
            abort(403, 'Unauthorized');
        }

        $bookmark->markAsUnread();

        return response()->json([
            'ok' => true,
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function updateNotes(Request $request, Bookmark $bookmark): JsonResponse
    {
        $readerToken = $this->readReaderToken($request);

        if (! $readerToken || $bookmark->reader_token !== $readerToken) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:5000',
        ]);

        $bookmark->update([
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'notes' => $bookmark->notes,
        ]);
    }

    // Anonymous (reader_token) endpoints
    public function storeAnonymous(StoreBookmarkRequest $request): JsonResponse|RedirectResponse
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

    public function destroyAnonymous(DestroyBookmarkRequest $request): JsonResponse|RedirectResponse
    {
        $readerToken = $this->readReaderToken($request);
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

    public function toggleAnonymous(ToggleBookmarkRequest $request): JsonResponse
    {
        // no-op
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
