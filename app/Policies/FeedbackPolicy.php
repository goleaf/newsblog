<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeedbackPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any feedback.
     */
    public function viewAny(User $user): bool
    {
        // All Nova users (admin, editor, author) can view feedback
        return in_array($user->role, ['admin', 'editor', 'author']);
    }

    /**
     * Determine whether the user can view the feedback.
     */
    public function view(User $user, Feedback $feedback): bool
    {
        // Admin can view all feedback
        if ($user->role === 'admin') {
            return true;
        }

        // Users can view their own feedback
        return $user->id === $feedback->user_id;
    }

    /**
     * Determine whether the user can create feedback.
     */
    public function create(User $user): bool
    {
        // All Nova users can create feedback
        return in_array($user->role, ['admin', 'editor', 'author']);
    }

    /**
     * Determine whether the user can update the feedback.
     */
    public function update(User $user, Feedback $feedback): bool
    {
        // Admin can update any feedback
        if ($user->role === 'admin') {
            return true;
        }

        // Users can update their own feedback if status is 'new'
        return $user->id === $feedback->user_id && $feedback->status === 'new';
    }

    /**
     * Determine whether the user can delete the feedback.
     */
    public function delete(User $user, Feedback $feedback): bool
    {
        // Only admin can delete feedback
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the feedback.
     */
    public function restore(User $user, Feedback $feedback): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the feedback.
     */
    public function forceDelete(User $user, Feedback $feedback): bool
    {
        return $user->role === 'admin';
    }
}
