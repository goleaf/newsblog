<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any media.
     */
    public function viewAny(User $user): bool
    {
        // Admin and editor can view all media
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        // Users can view their own media
        return true;
    }

    /**
     * Determine whether the user can view the media.
     */
    public function view(User $user, Media $media): bool
    {
        // Admin and editor can view any media
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        // Users can view their own media
        return $user->id === $media->user_id;
    }

    /**
     * Determine whether the user can create media.
     */
    public function create(User $user): bool
    {
        // Authenticated users can upload media
        return true;
    }

    /**
     * Determine whether the user can update the media.
     */
    public function update(User $user, Media $media): bool
    {
        // Admin and editor can update any media
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        // Users can update their own media
        return $user->id === $media->user_id;
    }

    /**
     * Determine whether the user can delete the media.
     */
    public function delete(User $user, Media $media): bool
    {
        // Admin and editor can delete any media
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        // Users can delete their own media
        return $user->id === $media->user_id;
    }

    /**
     * Determine whether the user can restore the media.
     */
    public function restore(User $user, Media $media): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can permanently delete the media.
     */
    public function forceDelete(User $user, Media $media): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }
}
