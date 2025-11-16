<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function follow(Request $request, int $userId): JsonResponse
    {
        $auth = $request->user();
        abort_unless($auth, 401);

        if ($auth->id === $userId) {
            return response()->json(['message' => 'Cannot follow yourself'], 422);
        }

        $target = User::findOrFail($userId);

        $exists = Follow::query()
            ->where('follower_id', $auth->id)
            ->where('followed_id', $target->id)
            ->exists();

        if (! $exists) {
            Follow::create([
                'follower_id' => $auth->id,
                'followed_id' => $target->id,
            ]);

            // Record activity
            \App\Models\ActivityLog::create([
                'log_name' => 'social',
                'description' => $auth->name.' followed '.$target->name,
                'subject_type' => User::class,
                'subject_id' => $target->id,
                'event' => 'followed',
                'causer_type' => User::class,
                'causer_id' => $auth->id,
                'properties' => [
                    'follower_id' => $auth->id,
                    'followed_id' => $target->id,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function unfollow(Request $request, int $userId): JsonResponse
    {
        $auth = $request->user();
        abort_unless($auth, 401);

        $target = User::findOrFail($userId);

        Follow::query()
            ->where('follower_id', $auth->id)
            ->where('followed_id', $target->id)
            ->delete();

        // Record activity
        \App\Models\ActivityLog::create([
            'log_name' => 'social',
            'description' => $auth->name.' unfollowed '.$target->name,
            'subject_type' => User::class,
            'subject_id' => $target->id,
            'event' => 'unfollowed',
            'causer_type' => User::class,
            'causer_id' => $auth->id,
            'properties' => [
                'follower_id' => $auth->id,
                'followed_id' => $target->id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(null, 204);
    }

    public function followers(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $followers = Follow::query()
            ->where('followed_id', $user->id)
            ->with(['follower' => function ($q) {
                $q->select('id', 'name', 'email');
            }])
            ->paginate(20);

        return response()->json([
            'data' => $followers->map(fn ($f) => new UserResource($f->follower)),
            'total' => $followers->total(),
            'links' => [
                'next' => $followers->nextPageUrl(),
                'prev' => $followers->previousPageUrl(),
            ],
        ]);
    }

    public function following(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $following = Follow::query()
            ->where('follower_id', $user->id)
            ->with(['followed' => function ($q) {
                $q->select('id', 'name', 'email');
            }])
            ->paginate(20);

        return response()->json([
            'data' => $following->map(fn ($f) => new UserResource($f->followed)),
            'total' => $following->total(),
            'links' => [
                'next' => $following->nextPageUrl(),
                'prev' => $following->previousPageUrl(),
            ],
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $auth = $request->user();
        abort_unless($auth, 401);

        $followingIds = Follow::query()
            ->where('follower_id', $auth->id)
            ->pluck('followed_id')
            ->all();

        $suggestions = \App\Models\User::query()
            ->where('id', '!=', $auth->id)
            ->whereNotIn('id', $followingIds)
            ->withCount(['posts' => function ($q) {
                $q->where('status', 'published');
            }])
            ->orderByDesc('posts_count')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'data' => $suggestions->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'posts_count' => $u->posts_count ?? 0,
            ]),
        ]);
    }
}
