<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FollowController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['followers', 'following']);
    }

    /**
     * Follow a user.
     */
    public function follow(User $user): JsonResponse|RedirectResponse
    {
        $currentUser = Auth::user();

        // Prevent self-following
        if ($currentUser->id === $user->id) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'You cannot follow yourself.',
                ], 400);
            }

            return back()->with('error', 'You cannot follow yourself.');
        }

        // Check if already following
        if ($currentUser->isFollowing($user)) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'You are already following this user.',
                ], 400);
            }

            return back()->with('error', 'You are already following this user.');
        }

        // Create follow relationship
        Follow::create([
            'follower_id' => $currentUser->id,
            'followed_id' => $user->id,
        ]);

        // TODO: Dispatch notification to followed user

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Successfully followed user.',
                'following' => true,
            ]);
        }

        return back()->with('success', 'You are now following '.$user->name.'.');
    }

    /**
     * Unfollow a user.
     */
    public function unfollow(User $user): JsonResponse|RedirectResponse
    {
        $currentUser = Auth::user();

        // Check if following
        if (! $currentUser->isFollowing($user)) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'You are not following this user.',
                ], 400);
            }

            return back()->with('error', 'You are not following this user.');
        }

        // Delete follow relationship
        Follow::query()
            ->where('follower_id', $currentUser->id)
            ->where('followed_id', $user->id)
            ->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Successfully unfollowed user.',
                'following' => false,
            ]);
        }

        return back()->with('success', 'You have unfollowed '.$user->name.'.');
    }

    /**
     * List followers of a user.
     */
    public function followers(User $user): View
    {
        $followers = $user->followers()
            ->with('follower')
            ->latest()
            ->paginate(20);

        return view('follows.followers', [
            'user' => $user,
            'followers' => $followers,
        ]);
    }

    /**
     * List users that a user is following.
     */
    public function following(User $user): View
    {
        $following = $user->following()
            ->with('followed')
            ->latest()
            ->paginate(20);

        return view('follows.following', [
            'user' => $user,
            'following' => $following,
        ]);
    }

    /**
     * Check if the authenticated user is following a specific user.
     */
    public function checkStatus(User $user): JsonResponse
    {
        $currentUser = Auth::user();

        if (! $currentUser) {
            return response()->json([
                'following' => false,
            ]);
        }

        return response()->json([
            'following' => $currentUser->isFollowing($user),
        ]);
    }
}
