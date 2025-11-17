<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookmarkResource;
use App\Http\Resources\PostResource;
use App\Models\Bookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Bookmarks
 *
 * API endpoints for managing article bookmarks and reading lists.
 */
class BookmarkController extends Controller
{
    /**
     * List Bookmarks
     *
     * Get all bookmarks for the authenticated user.
     *
     * @authenticated
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "post": {
     *         "id": 5,
     *         "title": "Laravel Best Practices",
     *         "slug": "laravel-best-practices",
     *         "excerpt": "Learn the best practices...",
     *         "author": {
     *           "id": 2,
     *           "name": "Jane Smith"
     *         }
     *       },
     *       "created_at": "2024-01-01T12:00:00.000000Z"
     *     }
     *   ],
     *   "links": {...},
     *   "meta": {...}
     * }
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $bookmarks = Bookmark::query()
            ->where('user_id', $user->id)
            ->with([
                'post:id,title,slug,excerpt,featured_image,published_at,reading_time,view_count,user_id,category_id',
                'post.user:id,name,avatar',
                'post.category:id,name,slug',
                'post.tags:id,name,slug',
            ])
            ->select(['id', 'user_id', 'post_id', 'collection_id', 'is_read', 'read_at', 'notes', 'created_at'])
            ->latest('id')
            ->paginate(20);

        $posts = $bookmarks->map(fn ($b) => $b->post)->filter();

        return response()->json([
            'data' => PostResource::collection($posts->all()),
            'total' => $bookmarks->total(),
            'links' => [
                'next' => $bookmarks->nextPageUrl(),
                'prev' => $bookmarks->previousPageUrl(),
            ],
        ]);
    }

    /**
     * Create a bookmark
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'collection_id' => ['nullable', 'integer', 'exists:bookmark_collections,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();

        // Check if bookmark already exists
        $existing = Bookmark::where('user_id', $user->id)
            ->where('post_id', $validated['post_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Bookmark already exists',
                'data' => new BookmarkResource($existing),
            ], 200);
        }

        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'post_id' => $validated['post_id'],
            'collection_id' => $validated['collection_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Bookmark created successfully',
            'data' => new BookmarkResource($bookmark->load('post')),
        ], 201);
    }

    /**
     * Remove a bookmark
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();

        $bookmark = Bookmark::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $bookmark->delete();

        return response()->json([
            'message' => 'Bookmark removed successfully',
        ], 200);
    }
}
