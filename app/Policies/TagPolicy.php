<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any tags.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can view the tag.
     */
    public function view(User $user, Tag $tag): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can create tags.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can update the tag.
     */
    public function update(User $user, Tag $tag): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can delete the tag.
     */
    public function delete(User $user, Tag $tag): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can restore the tag.
     */
    public function restore(User $user, Tag $tag): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can permanently delete the tag.
     */
    public function forceDelete(User $user, Tag $tag): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }
}
