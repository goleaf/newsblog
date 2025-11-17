<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * @group Users
 *
 * API endpoints for managing user profiles and accounts.
 */
class UserController extends Controller
{
    /**
     * Get Current User
     *
     * Retrieve the authenticated user's profile information.
     *
     * @authenticated
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "bio": "Software developer and tech enthusiast",
     *     "avatar": "https://example.com/avatars/john.jpg",
     *     "profile": {
     *       "website": "https://johndoe.com",
     *       "twitter_handle": "@johndoe",
     *       "github_username": "johndoe",
     *       "location": "San Francisco, CA",
     *       "company": "Tech Corp",
     *       "job_title": "Senior Developer"
     *     },
     *     "created_at": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     */
    public function me(): JsonResponse
    {
        $user = Auth::user();

        return response()->json(new UserResource($user));
    }

    /**
     * Update Current User
     *
     * Update the authenticated user's profile information.
     *
     * @authenticated
     *
     * @bodyParam name string optional The user's full name. Example: John Doe
     * @bodyParam email string optional The user's email address. Must be unique. Example: john@example.com
     * @bodyParam bio string optional User biography. Max 500 characters. Example: Software developer and tech enthusiast
     * @bodyParam website string optional Personal website URL. Example: https://johndoe.com
     * @bodyParam twitter_handle string optional Twitter username. Example: @johndoe
     * @bodyParam github_username string optional GitHub username. Example: johndoe
     * @bodyParam linkedin_url string optional LinkedIn profile URL. Example: https://linkedin.com/in/johndoe
     * @bodyParam location string optional User location. Example: San Francisco, CA
     * @bodyParam company string optional Company name. Example: Tech Corp
     * @bodyParam job_title string optional Job title. Example: Senior Developer
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "bio": "Software developer and tech enthusiast",
     *     "profile": {
     *       "website": "https://johndoe.com",
     *       "twitter_handle": "@johndoe",
     *       "location": "San Francisco, CA"
     *     }
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'twitter_handle' => ['sometimes', 'nullable', 'string', 'max:50'],
            'github_username' => ['sometimes', 'nullable', 'string', 'max:50'],
            'linkedin_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'location' => ['sometimes', 'nullable', 'string', 'max:100'],
            'company' => ['sometimes', 'nullable', 'string', 'max:100'],
            'job_title' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        // Update user basic fields
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['bio'])) {
            $user->bio = $validated['bio'];
        }
        $user->save();

        // Update profile fields if they exist
        $profileFields = ['website', 'twitter_handle', 'github_username', 'linkedin_url', 'location', 'company', 'job_title'];
        $profileData = array_intersect_key($validated, array_flip($profileFields));

        if (! empty($profileData) && $user->profile) {
            $user->profile->update($profileData);
        }

        return response()->json(new UserResource($user->fresh()));
    }

    /**
     * Get User Profile
     *
     * Retrieve a user's public profile information.
     *
     * @urlParam id integer required The user ID. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "bio": "Software developer",
     *     "avatar": "https://example.com/avatars/john.jpg",
     *     "profile": {
     *       "website": "https://johndoe.com",
     *       "twitter_handle": "@johndoe",
     *       "location": "San Francisco, CA"
     *     },
     *     "posts_count": 25,
     *     "followers_count": 150,
     *     "following_count": 75
     *   }
     * }
     * @response 404 {
     *   "message": "User not found"
     * }
     */
    public function show($id): JsonResponse
    {
        $user = User::query()
            ->with([
                'profile:user_id,website,twitter_handle,github_username,linkedin_url,location,company,job_title',
                'preferences:user_id,theme,reading_list_public,profile_visibility',
            ])
            ->withCount([
                'posts' => function ($query) {
                    $query->published();
                },
                'comments' => function ($query) {
                    $query->where('status', 'approved');
                },
                'followers',
                'following',
            ])
            ->select(['id', 'name', 'email', 'avatar', 'bio', 'role', 'status', 'created_at'])
            ->findOrFail($id);

        return response()->json(new UserResource($user));
    }

    /**
     * Get User Suggestions
     *
     * Get a list of suggested users to follow based on activity and popularity.
     *
     * @authenticated
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 2,
     *       "name": "Jane Smith",
     *       "bio": "Tech writer and blogger",
     *       "avatar": "https://example.com/avatars/jane.jpg",
     *       "posts_count": 42,
     *       "followers_count": 320
     *     }
     *   ]
     * }
     */
    public function suggestions(): JsonResponse
    {
        $currentUser = Auth::user();

        // Get users that the current user is not following
        $followingIds = $currentUser->following()->pluck('followed_id');

        $suggestions = User::query()
            ->whereNotIn('id', $followingIds->push($currentUser->id))
            ->where('status', \App\Enums\UserStatus::Active)
            ->with([
                'profile:user_id,website,twitter_handle,location',
            ])
            ->withCount([
                'posts' => function ($query) {
                    $query->published();
                },
                'followers',
            ])
            ->select(['id', 'name', 'avatar', 'bio', 'created_at'])
            ->orderByDesc('posts_count')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => UserResource::collection($suggestions),
        ]);
    }
}
