<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Resources\TagResource;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::query()
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => TagResource::collection($tags->items()),
            'total' => $tags->total(),
            'links' => [
                'next' => $tags->nextPageUrl(),
                'prev' => $tags->previousPageUrl(),
            ],
        ]);
    }

    public function articles(int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);

        $posts = Post::published()
            ->byTag($tag->id)
            ->with(['user:id,name', 'category:id,name,slug'])
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return response()->json([
            'data' => PostResource::collection($posts->items()),
            'total' => $posts->total(),
            'links' => [
                'next' => $posts->nextPageUrl(),
                'prev' => $posts->previousPageUrl(),
            ],
        ]);
    }
}
