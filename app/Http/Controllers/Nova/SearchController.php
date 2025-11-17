<?php

namespace App\Http\Controllers\Nova;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function posts(Request $request): JsonResponse
    {
        $search = (string) $request->query('search', '');

        $query = Post::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $items = $query->latest('id')->limit(50)->get(['id', 'title', 'excerpt', 'content']);

        return response()->json(['data' => $items]);
    }

    public function users(Request $request): JsonResponse
    {
        $search = (string) $request->query('search', '');

        $query = User::query();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $items = $query->latest('id')->limit(50)->get(['id', 'name', 'email']);

        return response()->json(['data' => $items]);
    }

    public function categories(Request $request): JsonResponse
    {
        $search = (string) $request->query('search', '');
        $query = Category::query();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $items = $query->latest('id')->limit(50)->get(['id', 'name', 'description']);

        return response()->json(['data' => $items]);
    }

    public function tags(Request $request): JsonResponse
    {
        $search = (string) $request->query('search', '');
        $query = Tag::query();
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }
        $items = $query->orderBy('name')->limit(100)->get(['id', 'name']);

        return response()->json(['data' => $items]);
    }

    public function comments(Request $request): JsonResponse
    {
        $search = (string) $request->query('search', '');
        $query = Comment::query();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%")
                    ->orWhere('author_email', 'like', "%{$search}%");
            });
        }
        $items = $query->latest('id')->limit(100)->get(['id', 'content', 'author_name', 'author_email']);

        return response()->json(['data' => $items]);
    }

    public function media(Request $request): JsonResponse
    {
        $search = (string) $request->query('search', '');
        $query = Media::query();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('file_name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }
        $items = $query->latest('id')->limit(100)->get(['id', 'file_name', 'title', 'alt_text']);

        return response()->json(['data' => $items]);
    }
}
