<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookmarkResource;
use App\Http\Resources\ReadingListResource;
use App\Models\Bookmark;
use App\Models\BookmarkCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingListController extends Controller
{
    /**
     * List reading lists for the authenticated user.
     *
     * @group Reading Lists
     *
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $lists = $request->user()->bookmarkCollections()->withCount('bookmarks')->paginate(20);

        return response()->json([
            'data' => $lists->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'is_public' => (bool) $c->is_public,
                    'bookmarks_count' => $c->bookmarks_count,
                ];
            }),
            'total' => $lists->total(),
            'links' => [
                'next' => $lists->nextPageUrl(),
                'prev' => $lists->previousPageUrl(),
            ],
        ]);
    }

    /**
     * Create a new reading list.
     *
     * @group Reading Lists
     *
     * @authenticated
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['boolean'],
        ]);

        $order = (int) $request->user()->bookmarkCollections()->max('order') + 1;
        $collection = $request->user()->bookmarkCollections()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? false,
            'order' => $order,
        ]);

        return response()->json(new ReadingListResource($collection), 201);
    }

    /**
     * Show a reading list (owner or public).
     *
     * @group Reading Lists
     *
     * @authenticated
     */
    public function show(Request $request, BookmarkCollection $collection): JsonResponse
    {
        if ($collection->user_id !== $request->user()->id && ! $collection->is_public) {
            abort(403);
        }

        $collection->load(['bookmarks.post' => function ($q) {
            $q->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at']);
        }]);

        $resource = new ReadingListResource($collection);

        return response()->json(['data' => $resource->toArray(request())]);
    }

    /**
     * Update a reading list.
     *
     * @group Reading Lists
     *
     * @authenticated
     */
    public function update(Request $request, BookmarkCollection $collection): JsonResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['boolean'],
        ]);

        $collection->update($validated);

        return response()->json(new ReadingListResource($collection));
    }

    /**
     * Delete a reading list.
     *
     * @group Reading Lists
     *
     * @authenticated
     */
    public function destroy(Request $request, BookmarkCollection $collection): JsonResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 403);
        $collection->bookmarks()->update(['collection_id' => null]);
        $collection->delete();

        return response()->json(null, 204);
    }

    /**
     * Add an item (post) to a reading list.
     *
     * @group Reading Lists
     *
     * @authenticated
     */
    public function addItem(Request $request, BookmarkCollection $collection): JsonResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'post_id' => ['required', 'exists:posts,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        // If a bookmark already exists for this post by this user, move to collection
        $bookmark = Bookmark::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'post_id' => $validated['post_id'],
            ],
            [
                'order' => 0,
            ]
        );

        $bookmark->update([
            'collection_id' => $collection->id,
            'note' => $validated['note'] ?? $bookmark->note,
        ]);

        return response()->json(new BookmarkResource($bookmark->load('post')), 201);
    }

    /**
     * Remove an item from a reading list.
     *
     * @group Reading Lists
     *
     * @authenticated
     */
    public function removeItem(Request $request, BookmarkCollection $collection, Bookmark $bookmark): JsonResponse
    {
        abort_unless($collection->user_id === $request->user()->id && $bookmark->user_id === $request->user()->id, 403);

        if ($bookmark->collection_id !== $collection->id) {
            abort(404);
        }

        $bookmark->update(['collection_id' => null]);

        return response()->json(null, 204);
    }

    /**
     * Reorder items in a reading list.
     *
     * @group Reading Lists
     *
     * @authenticated
     */
    public function reorder(Request $request, BookmarkCollection $collection): JsonResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'bookmark_ids' => ['required', 'array'],
            'bookmark_ids.*' => ['integer', 'exists:bookmarks,id'],
        ]);

        foreach ($validated['bookmark_ids'] as $index => $id) {
            Bookmark::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->where('collection_id', $collection->id)
                ->update(['order' => $index]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Generate a share link for a reading list.
     *
     * @group Reading Lists
     *
     * @authenticated
     */
    public function share(Request $request, BookmarkCollection $collection): JsonResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 403);

        if (! $collection->share_token) {
            $collection->update(['share_token' => BookmarkCollection::generateShareToken()]);
        }

        $url = route('reading-lists.shared.show', ['token' => $collection->share_token]);

        return response()->json([
            'share_url' => $url,
            'share_token' => $collection->share_token,
        ]);
    }

    /**
     * Revoke share link for a reading list.
     *
     * @group Reading Lists
     *
     * @authenticated
     */
    public function revokeShare(Request $request, BookmarkCollection $collection): JsonResponse
    {
        abort_unless($collection->user_id === $request->user()->id, 403);
        $collection->update(['share_token' => null]);

        return response()->json(null, 204);
    }

    /**
     * View a shared reading list (public).
     *
     * @group Reading Lists
     */
    public function sharedShow(string $token): JsonResponse
    {
        $collection = BookmarkCollection::where('share_token', $token)
            ->with(['bookmarks.post' => function ($q) {
                $q->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at']);
            }])->first();

        if (! $collection) {
            abort(404);
        }

        return response()->json(new ReadingListResource($collection));
    }
}
