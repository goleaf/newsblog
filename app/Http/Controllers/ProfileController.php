<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateEmailPreferencesRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        // Get user's authored posts if they are an author
        $authoredPosts = collect();
        if ($user->isAuthor() || $user->isEditor() || $user->isAdmin()) {
            $authoredPosts = $user->posts()
                ->where('status', 'published')
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
        ];

        return view('profile.show', compact('user', 'authoredPosts', 'recentComments', 'stats'));
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

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if it exists and is not the default
            if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
                \Storage::disk('public')->delete($user->avatar);
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

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
}
