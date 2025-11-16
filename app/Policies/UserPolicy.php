<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     * Requirements: 3.1, 16.3
     */
    public function viewAny(User $user): bool
    {
        // Admins and editors can view the user management interface
        return in_array($user->role, [UserRole::Admin, UserRole::Editor]);
    }

    /**
     * Determine whether the user can view the user profile.
     * Requirements: 3.1, 16.3
     */
    public function view(User $user, User $model): bool
    {
        // Admins and editors can view any user profile
        if (in_array($user->role, [UserRole::Admin, UserRole::Editor])) {
            return true;
        }

        // Users can view their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create users.
     * Requirements: 3.1, 16.3
     */
    public function create(User $user): bool
    {
        // Only admins can create users
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can update the user profile.
     * Requirements: 3.1, 16.3
     */
    public function update(User $user, User $model): bool
    {
        // Admins can update any user
        if ($user->role === UserRole::Admin) {
            return true;
        }

        // Users can update their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the user.
     * Requirements: 3.1, 16.3
     */
    public function delete(User $user, User $model): bool
    {
        // Admins cannot delete themselves
        if ($user->role === UserRole::Admin && $user->id === $model->id) {
            return false;
        }

        // Admins can delete any other user
        if ($user->role === UserRole::Admin) {
            return true;
        }

        // Users can delete their own account
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can restore the user.
     * Requirements: 3.1, 16.3
     */
    public function restore(User $user, User $model): bool
    {
        // Only admins can restore deleted users
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can permanently delete the user.
     * Requirements: 3.1, 16.3
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only admins can permanently delete users (except themselves)
        return $user->role === UserRole::Admin && $user->id !== $model->id;
    }
}
