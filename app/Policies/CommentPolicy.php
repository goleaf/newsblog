<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any comments.
     * Requirements: 5.1, 5.5, 14.2
     */
    public function viewAny(User $user): bool
    {
        // Authors, editors, moderators, and admins can view the comment management interface
        return in_array($user->role, [UserRole::Author, UserRole::Editor, UserRole::Moderator, UserRole::Admin]);
    }

    /**
     * Determine whether the user can view the comment.
     * Requirements: 5.1, 5.5, 14.2
     */
    public function view(User $user, Comment $comment): bool
    {
        // Admins, editors, and moderators can view any comment
        if (in_array($user->role, [UserRole::Admin, UserRole::Editor, UserRole::Moderator])) {
            return true;
        }

        // Users can view their own comments
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can create comments.
     * Requirements: 5.1, 5.5, 14.2
     */
    public function create(User $user): bool
    {
        // All authenticated users can create comments
        return true;
    }

    /**
     * Determine whether the user can update the comment.
     * Requirements: 5.1, 5.5, 14.2
     */
    public function update(User $user, Comment $comment): bool
    {
        // Admins, editors, and moderators can update any comment
        if (in_array($user->role, [UserRole::Admin, UserRole::Editor, UserRole::Moderator])) {
            return true;
        }

        // Users can update their own comments
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can delete the comment.
     * Requirements: 5.1, 5.5, 14.2
     */
    public function delete(User $user, Comment $comment): bool
    {
        // Admins, editors, and moderators can delete any comment
        if (in_array($user->role, [UserRole::Admin, UserRole::Editor, UserRole::Moderator])) {
            return true;
        }

        // Users can delete their own comments
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can moderate the comment.
     * Requirements: 5.1, 5.5, 14.2
     */
    public function moderate(User $user, Comment $comment): bool
    {
        // Only moderators and admins can moderate comments
        return in_array($user->role, [UserRole::Moderator, UserRole::Admin]);
    }

    /**
     * Determine whether the user can restore the comment.
     * Requirements: 5.1, 5.5, 14.2
     */
    public function restore(User $user, Comment $comment): bool
    {
        // Admins, editors, and moderators can restore deleted comments
        return in_array($user->role, [UserRole::Admin, UserRole::Editor, UserRole::Moderator]);
    }

    /**
     * Determine whether the user can permanently delete the comment.
     * Requirements: 5.1, 5.5, 14.2
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        // Only admins can permanently delete comments
        return $user->role === UserRole::Admin;
    }
}
