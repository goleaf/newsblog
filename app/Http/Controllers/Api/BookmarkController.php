<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Bookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $bookmarks = Bookmark::query()
            ->where('user_id', $user->id)
            ->with(['post' => function ($q) {
                $q->with(['user:id,name', 'category:id,name,slug']);
            }])
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
}
