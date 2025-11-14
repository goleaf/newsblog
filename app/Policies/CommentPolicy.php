<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any comments.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor', 'author']);
    }

    /**
     * Determine whether the user can view the comment.
     */
    public function view(User $user, Comment $comment): bool
    {
        // Admin and editor can view any comment
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        // Users can view their own comments
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can create comments.
     */
    public function create(User $user): bool
    {
        // Authenticated users can create comments
        return true;
    }

    /**
     * Determine whether the user can update the comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        // Admin and editor can update any comment
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        // Users can update their own comments
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // Admin and editor can delete any comment
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        // Users can delete their own comments
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can restore the comment.
     */
    public function restore(User $user, Comment $comment): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can permanently delete the comment.
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }
}
