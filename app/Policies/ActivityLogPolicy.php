<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any activity logs.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can view the activity log.
     */
    public function view(User $user, ActivityLog $activityLog): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can create activity logs.
     * Activity logs are system-generated only.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the activity log.
     * Activity logs are read-only.
     */
    public function update(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the activity log.
     * Activity logs are read-only.
     */
    public function delete(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the activity log.
     * Activity logs are read-only.
     */
    public function restore(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the activity log.
     * Activity logs are read-only.
     */
    public function forceDelete(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }
}
