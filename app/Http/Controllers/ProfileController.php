<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateEmailPreferencesRequest;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Models\User;
use App\Services\AvatarUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        protected AvatarUploadService $avatarService
    ) {}

    /**
     * Display the authenticated user's profile.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        return $this->showProfile($user, true);
    }

    /**
     * Display a public user profile.
     */
    public function showPublic(User $user): View
    {
        return $this->showProfile($user, false);
    }

    /**
     * Common logic for displaying user profiles.
     */
    protected function showProfile(User $user, bool $isOwnProfile): View
    {
        // Get user's authored posts if they are an author
        $authoredPosts = collect();
        if ($user->isAuthor() || $user->isEditor() || $user->isAdmin()) {
            $authoredPosts = $user->posts()
                ->where('status', 'published')
                ->with(['category:id,name,slug,color_code'])
                ->latest()
                ->limit(6)
                ->get();
        }

        // Get recent comments
        $recentComments = $user->comments()
            ->with('post:id,title,slug')
            ->latest()
            ->limit(5)
            ->get();

        // Get stats
        $stats = [
            'total_bookmarks' => $user->bookmarks()->count(),
            'total_comments' => $user->comments()->count(),
            'total_reactions' => $user->reactions()->count(),
            'total_posts' => $user->posts()->where('status', 'published')->count(),
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
        ];

        // Check if current user is following this profile (if authenticated)
        $isFollowing = false;
        if (Auth::check() && ! $isOwnProfile) {
            $isFollowing = Auth::user()->isFollowing($user);
        }

        return view('profile.show', compact(
            'user',
            'authoredPosts',
            'recentComments',
            'stats',
            'isOwnProfile',
            'isFollowing'
        ));
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Handle avatar upload using dedicated service
        if ($request->hasFile('avatar')) {
            $avatarPath = $this->avatarService->upload(
                $request->file('avatar'),
                $user->avatar
            );
            $data['avatar'] = $avatarPath;
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Update or create user profile if additional fields are present
        $profileData = [];
        if (isset($data['website'])) {
            $profileData['website'] = $data['website'];
        }
        if (isset($data['location'])) {
            $profileData['location'] = $data['location'];
        }
        if (isset($data['social_links'])) {
            $profileData['social_links'] = $data['social_links'];
        }

        if (! empty($profileData)) {
            $profileData['user_id'] = $user->id;
            \App\Models\UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Update the user's email preferences.
     */
    public function updateEmailPreferences(UpdateEmailPreferencesRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Convert checkbox values to boolean
        $preferences = [
            'comment_replies' => isset($validated['preferences']['comment_replies']),
            'post_published' => isset($validated['preferences']['post_published']),
            'comment_approved' => isset($validated['preferences']['comment_approved']),
            'series_updated' => isset($validated['preferences']['series_updated']),
            'newsletter' => isset($validated['preferences']['newsletter']),
            'frequency' => $validated['preferences']['frequency'],
        ];

        $request->user()->updateEmailPreferences($preferences);

        return Redirect::route('profile.edit')->with('status', 'email-preferences-updated');
    }

    /**
     * Update the user's general preferences.
     */
    public function updatePreferences(UpdatePreferencesRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $request->user();

        // Update or create user preferences
        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            ['preferences' => $validated['preferences']]
        );

        return Redirect::route('profile.edit')->with('status', 'preferences-updated');
    }

    /**
     * Upload avatar image.
     */
    public function uploadAvatar(UploadAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();

        $avatarPath = $this->avatarService->upload(
            $request->file('avatar'),
            $user->avatar
        );

        $user->update(['avatar' => $avatarPath]);

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }
}
