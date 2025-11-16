<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Posts
 *
 * API endpoints for managing and retrieving blog posts.
 */
class PostController extends Controller
{
    /**
     * List Posts
     *
     * Get a paginated list of published posts. You can filter by category, tag, or search term.
     *
     * @queryParam category string Filter by category slug. Example: technology
     * @queryParam tag string Filter by tag slug. Example: laravel
     * @queryParam search string Search in title and content. Example: php
     * @queryParam page int Page number for pagination. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Example Post",
     *       "slug": "example-post",
     *       "excerpt": "This is an example post excerpt...",
     *       "published_at": "2024-01-01T00:00:00.000000Z",
     *       "author": {
     *         "id": 1,
     *         "name": "John Doe"
     *       },
     *       "category": {
     *         "id": 1,
     *         "name": "Technology"
     *       }
     *     }
     *   ],
     *   "links": {...},
     *   "meta": {...}
     * }
     */
    public function index(Request $request)
    {
        $query = Post::published()->with(['user', 'category', 'categories', 'tags']);

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $posts = $query->recent()->paginate(15);

        return PostResource::collection($posts);
    }

    /**
     * Get Single Post
     *
     * Retrieve a single published post by its slug.
     *
     * @urlParam slug string required The post slug. Example: example-post
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "title": "Example Post",
     *     "slug": "example-post",
     *     "content": "Full post content...",
     *     "published_at": "2024-01-01T00:00:00.000000Z",
     *     "author": {...},
     *     "category": {...},
     *     "tags": [...],
     *     "comments_count": 5
     *   }
     * }
     * @response 404 {
     *   "message": "Post not found"
     * }
     */
    public function show($slug)
    {
        $post = Post::where('slug', $slug)
            ->published()
            ->with(['user', 'category', 'categories', 'tags', 'comments' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->firstOrFail();

        return new PostResource($post);
    }

    /**
     * Create Post (auth)
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $post = Post::create($data);

        return response()->json(new PostResource($post->fresh(['user', 'category'])), 201);
    }

    /**
     * Update Post (auth)
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $post->update($request->validated());

        return response()->json(new PostResource($post->fresh(['user', 'category'])));
    }

    /**
     * Delete Post (auth)
     */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        $this->authorize('delete', $post);
        $post->delete();

        return response()->json(null, 204);
    }
}
