<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Cookie as CookieContract;

class GdprService
{
    /**
     * Store cookie consent.
     */
    public function storeConsent(bool $accepted): CookieContract
    {
        return Cookie::make(
            'gdpr_consent',
            $accepted ? 'accepted' : 'declined',
            60 * 24 * 365, // 1 year
            '/',
            null,
            true, // secure
            true, // httpOnly
            false,
            'lax'
        );
    }

    /**
     * Withdraw consent and return cookies to delete.
     */
    public function withdrawConsent(): array
    {
        $cookies = [];

        // Delete consent cookie
        $cookies[] = Cookie::forget('gdpr_consent');

        // Delete any analytics or tracking cookies
        $cookies[] = Cookie::forget('_ga');
        $cookies[] = Cookie::forget('_gid');
        $cookies[] = Cookie::forget('_gat');

        return $cookies;
    }

    /**
     * Export all user data in a machine-readable format.
     */
    public function exportUserData(User $user): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at->toIso8601String(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
            ],
            'posts' => $user->posts()->get()->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'content' => $post->content,
                    'status' => $post->status->value,
                    'published_at' => $post->published_at?->toIso8601String(),
                    'created_at' => $post->created_at->toIso8601String(),
                ];
            })->toArray(),
            'comments' => $user->comments()->get()->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'post_id' => $comment->post_id,
                    'content' => $comment->content,
                    'status' => $comment->status->value,
                    'created_at' => $comment->created_at->toIso8601String(),
                ];
            })->toArray(),
            'bookmarks' => $user->bookmarks()->with('post')->get()->map(function ($bookmark) {
                return [
                    'post_title' => $bookmark->post->title,
                    'post_slug' => $bookmark->post->slug,
                    'is_read' => $bookmark->is_read,
                    'notes' => $bookmark->notes,
                    'created_at' => $bookmark->created_at->toIso8601String(),
                ];
            })->toArray(),
            'reactions' => $user->reactions()->get()->map(function ($reaction) {
                return [
                    'post_id' => $reaction->post_id,
                    'type' => $reaction->type,
                    'created_at' => $reaction->created_at->toIso8601String(),
                ];
            })->toArray(),
            'media' => $user->media()->get()->map(function ($media) {
                return [
                    'id' => $media->id,
                    'file_path' => $media->file_path,
                    'file_type' => $media->file_type,
                    'created_at' => $media->created_at->toIso8601String(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Delete all user data and anonymize references.
     */
    public function deleteUserData(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Anonymize user's posts instead of deleting them
            $user->posts()->update([
                'author_id' => null,
            ]);

            // Delete user's comments
            $user->comments()->delete();

            // Delete bookmarks
            $user->bookmarks()->delete();

            // Delete reading lists
            $user->readingLists()->delete();

            // Delete follows
            $user->followers()->detach();
            $user->following()->detach();

            // Delete activities
            $user->activities()->delete();

            // Delete notifications
            $user->notifications()->delete();

            // Delete social accounts
            $user->socialAccounts()->delete();

            // Delete user profile
            $user->profile?->delete();

            // Delete user preferences
            $user->preferences?->delete();

            // Delete notification preferences
            $user->notificationPreferences?->delete();

            // Delete API tokens
            $user->tokens()->delete();

            // Delete the user
            $user->delete();
        });
    }

    /**
     * Anonymize user data without deleting the account.
     */
    public function anonymizeUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Anonymize user information
            $user->update([
                'name' => 'Deleted User',
                'email' => 'deleted_'.$user->id.'@example.com',
                'avatar' => null,
                'bio' => null,
            ]);

            // Delete profile
            $user->profile?->delete();

            // Delete social accounts
            $user->socialAccounts()->delete();

            // Keep posts but anonymize author
            $user->posts()->update([
                'author_id' => null,
            ]);

            // Delete comments
            $user->comments()->delete();

            // Delete bookmarks
            $user->bookmarks()->delete();

            // Delete reading lists
            $user->readingLists()->delete();

            // Delete follows
            $user->followers()->detach();
            $user->following()->detach();

            // Delete activities
            $user->activities()->delete();

            // Delete notifications
            $user->notifications()->delete();

            // Delete API tokens
            $user->tokens()->delete();

            // Delete notification preferences
            $user->notificationPreferences?->delete();
        });
    }
}
