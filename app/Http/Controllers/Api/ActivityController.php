<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\ActivityLog;
use App\Models\Follow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function my(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $activities = ActivityLog::query()
            ->forCauser($user)
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'data' => ActivityResource::collection($activities->items()),
            'total' => $activities->total(),
            'links' => [
                'next' => $activities->nextPageUrl(),
                'prev' => $activities->previousPageUrl(),
            ],
        ]);
    }

    public function following(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $ids = Follow::query()
            ->where('follower_id', $user->id)
            ->pluck('followed_id');

        $activities = ActivityLog::query()
            ->where('causer_type', \App\Models\User::class)
            ->whereIn('causer_id', $ids)
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'data' => ActivityResource::collection($activities->items()),
            'total' => $activities->total(),
            'links' => [
                'next' => $activities->nextPageUrl(),
                'prev' => $activities->previousPageUrl(),
            ],
        ]);
    }
}
