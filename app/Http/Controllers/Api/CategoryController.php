<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PostResource;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => CategoryResource::collection($categories->items()),
            'total' => $categories->total(),
            'links' => [
                'next' => $categories->nextPageUrl(),
                'prev' => $categories->previousPageUrl(),
            ],
        ]);
    }

    public function articles(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $posts = Post::published()
            ->where('category_id', $category->id)
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
