<?php

namespace App\Policies;

use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsletterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any newsletters.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the newsletter.
     */
    public function view(User $user, Newsletter $newsletter): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can create newsletters.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the newsletter.
     */
    public function update(User $user, Newsletter $newsletter): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the newsletter.
     */
    public function delete(User $user, Newsletter $newsletter): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the newsletter.
     */
    public function restore(User $user, Newsletter $newsletter): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the newsletter.
     */
    public function forceDelete(User $user, Newsletter $newsletter): bool
    {
        return $user->role === 'admin';
    }
}
