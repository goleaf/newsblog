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
        $query = Post::published()
            ->with([
                'user:id,name,email,avatar',
                'category:id,name,slug',
                'categories:id,name,slug',
                'tags:id,name,slug',
            ])
            ->select([
                'id',
                'title',
                'slug',
                'excerpt',
                'content',
                'featured_image',
                'user_id',
                'category_id',
                'status',
                'published_at',
                'reading_time',
                'view_count',
                'created_at',
                'updated_at',
            ]);

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
     * Retrieve a single published post by its ID or slug.
     *
     * @urlParam id string required The post ID or slug. Example: 1 or example-post
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
    public function show($id)
    {
        $query = Post::published()
            ->with([
                'user:id,name,email,avatar,bio',
                'category:id,name,slug,description',
                'categories:id,name,slug',
                'tags:id,name,slug',
                'comments' => function ($query) {
                    $query->where('status', 'approved')
                        ->with(['user:id,name,avatar'])
                        ->select(['id', 'post_id', 'user_id', 'parent_id', 'content', 'created_at']);
                },
            ])
            ->withCount(['comments' => function ($query) {
                $query->where('status', 'approved');
            }]);

        // Check if the parameter is numeric (ID) or string (slug)
        if (is_numeric($id)) {
            $post = $query->where('id', $id)->firstOrFail();
        } else {
            $post = $query->where('slug', $id)->firstOrFail();
        }

        return new PostResource($post);
    }

    /**
     * Create Post
     *
     * Create a new article. Requires authentication.
     *
     * @authenticated
     *
     * @bodyParam title string required The article title. Must not exceed 255 characters. Example: Getting Started with Laravel
     * @bodyParam slug string optional The article slug. Auto-generated from title if not provided. Example: getting-started-with-laravel
     * @bodyParam excerpt string optional Short description of the article. Max 500 characters. Example: Learn the basics of Laravel framework
     * @bodyParam content string required The full article content in HTML or Markdown. Example: <p>Laravel is a web application framework...</p>
     * @bodyParam featured_image string optional URL to the featured image. Example: https://example.com/images/laravel.jpg
     * @bodyParam image_alt_text string optional Alt text for the featured image. Example: Laravel logo
     * @bodyParam status string required Article status. Must be one of: draft, published, scheduled. Example: published
     * @bodyParam category_id integer required The category ID. Example: 1
     * @bodyParam published_at datetime optional Publication date. Required if status is published. Example: 2024-01-01 12:00:00
     * @bodyParam scheduled_at datetime optional Scheduled publication date. Required if status is scheduled. Example: 2024-01-15 09:00:00
     * @bodyParam is_featured boolean optional Mark as featured article. Example: false
     * @bodyParam is_trending boolean optional Mark as trending article. Example: false
     * @bodyParam meta_title string optional SEO meta title. Max 70 characters. Example: Getting Started with Laravel - Complete Guide
     * @bodyParam meta_description string optional SEO meta description. Max 160 characters. Example: A comprehensive guide to getting started with Laravel framework
     * @bodyParam meta_keywords string optional SEO keywords. Example: laravel, php, framework, tutorial
     *
     * @response 201 {
     *   "data": {
     *     "id": 1,
     *     "title": "Getting Started with Laravel",
     *     "slug": "getting-started-with-laravel",
     *     "excerpt": "Learn the basics of Laravel framework",
     *     "content": "<p>Laravel is a web application framework...</p>",
     *     "featured_image": "https://example.com/images/laravel.jpg",
     *     "status": "published",
     *     "published_at": "2024-01-01T12:00:00.000000Z",
     *     "author": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "category": {
     *       "id": 1,
     *       "name": "Technology",
     *       "slug": "technology"
     *     }
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "title": ["The title field is required."],
     *     "content": ["The content field is required."]
     *   }
     * }
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $post = Post::create($data);

        return response()->json(new PostResource($post->fresh(['user', 'category'])), 201);
    }

    /**
     * Update Post
     *
     * Update an existing article. Requires authentication and ownership or admin role.
     *
     * @authenticated
     *
     * @urlParam post integer required The post ID. Example: 1
     *
     * @bodyParam title string required The article title. Example: Updated Laravel Guide
     * @bodyParam slug string optional The article slug. Example: updated-laravel-guide
     * @bodyParam excerpt string optional Short description. Example: Updated guide to Laravel
     * @bodyParam content string required The full article content. Example: <p>Updated content...</p>
     * @bodyParam featured_image string optional Featured image URL. Example: https://example.com/images/updated.jpg
     * @bodyParam status string required Article status: draft, published, scheduled. Example: published
     * @bodyParam category_id integer required The category ID. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "title": "Updated Laravel Guide",
     *     "slug": "updated-laravel-guide",
     *     "content": "<p>Updated content...</p>",
     *     "status": "published"
     *   }
     * }
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     * @response 404 {
     *   "message": "Post not found"
     * }
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $post->update($request->validated());

        return response()->json(new PostResource($post->fresh(['user', 'category'])));
    }

    /**
     * Delete Post
     *
     * Delete an article. Requires authentication and ownership or admin role.
     * The article will be soft-deleted and can be restored later.
     *
     * @authenticated
     *
     * @urlParam post integer required The post ID. Example: 1
     *
     * @response 204 scenario="Success"
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     * @response 404 {
     *   "message": "Post not found"
     * }
     */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        $this->authorize('delete', $post);
        $post->delete();

        return response()->json(null, 204);
    }
}
