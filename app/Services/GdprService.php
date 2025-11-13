<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GdprService
{
    /**
     * Export all user data in JSON format
     */
    public function exportUserData(User $user): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'bio' => $user->bio,
                'avatar' => $user->avatar,
                'status' => $user->status,
                'created_at' => $user->created_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
            'posts' => $user->posts()->get()->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'content' => $post->content,
                    'status' => $post->status,
                    'published_at' => $post->published_at?->toIso8601String(),
                    'created_at' => $post->created_at?->toIso8601String(),
                ];
            })->toArray(),
            'comments' => $user->comments()->get()->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'post_id' => $comment->post_id,
                    'content' => $comment->content,
                    'status' => $comment->status,
                    'created_at' => $comment->created_at?->toIso8601String(),
                ];
            })->toArray(),
            'bookmarks' => $user->bookmarks()->with('post:id,title,slug')->get()->map(function ($bookmark) {
                return [
                    'post_id' => $bookmark->post_id,
                    'post_title' => $bookmark->post?->title,
                    'created_at' => $bookmark->created_at?->toIso8601String(),
                ];
            })->toArray(),
            'reactions' => $user->reactions()->with('post:id,title')->get()->map(function ($reaction) {
                return [
                    'post_id' => $reaction->post_id,
                    'post_title' => $reaction->post?->title,
                    'type' => $reaction->type,
                    'created_at' => $reaction->created_at?->toIso8601String(),
                ];
            })->toArray(),
            'media' => $user->media()->get()->map(function ($media) {
                return [
                    'id' => $media->id,
                    'filename' => $media->filename,
                    'original_filename' => $media->original_filename,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'created_at' => $media->created_at?->toIso8601String(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Anonymize user data for account deletion
     */
    public function anonymizeUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Anonymize user data
            $user->update([
                'name' => 'Deleted User',
                'email' => 'deleted_'.Str::random(10).'@deleted.local',
                'password' => Hash::make(Str::random(32)),
                'bio' => null,
                'avatar' => null,
                'status' => 'deleted',
            ]);

            // Anonymize comments
            $user->comments()->update([
                'author_name' => 'Deleted User',
                'author_email' => 'deleted@deleted.local',
                'ip_address' => null,
                'user_agent' => null,
            ]);

            // Delete bookmarks
            $user->bookmarks()->delete();

            // Delete reactions
            $user->reactions()->delete();

            // Delete media files
            foreach ($user->media as $media) {
                // Delete physical files
                if ($media->disk && $media->path) {
                    \Storage::disk($media->disk)->delete($media->path);
                }
                $media->delete();
            }

            // Anonymize posts (keep content but remove author association)
            $user->posts()->update([
                'user_id' => null,
            ]);
        });
    }

    /**
     * Delete user account and all associated data
     */
    public function deleteUserAccount(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Delete all user relationships
            $user->bookmarks()->delete();
            $user->reactions()->delete();
            $user->comments()->delete();

            // Delete media files
            foreach ($user->media as $media) {
                if ($media->disk && $media->path) {
                    \Storage::disk($media->disk)->delete($media->path);
                }
                $media->delete();
            }

            // Delete posts
            $user->posts()->delete();

            // Finally delete the user
            $user->delete();
        });
    }

    /**
     * Check if user has given cookie consent
     */
    public function hasConsent(string $cookieName = 'gdpr_consent'): bool
    {
        return request()->cookie($cookieName) === 'accepted';
    }

    /**
     * Store cookie consent
     */
    public function storeConsent(bool $accepted): \Symfony\Component\HttpFoundation\Cookie
    {
        $value = $accepted ? 'accepted' : 'declined';

        return cookie('gdpr_consent', $value, 60 * 24 * 365); // 1 year
    }

    /**
     * Withdraw consent and delete non-essential cookies
     */
    public function withdrawConsent(): array
    {
        $cookiesToDelete = [
            'gdpr_consent',
            // Add other non-essential cookies here
        ];

        $cookies = [];
        foreach ($cookiesToDelete as $cookieName) {
            $cookies[] = cookie()->forget($cookieName);
        }

        return $cookies;
    }
}
