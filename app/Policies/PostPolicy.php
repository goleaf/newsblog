<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any posts.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor', 'author']);
    }

    /**
     * Determine whether the user can view the post.
     */
    public function view(?User $user, Post $post): bool
    {
        // Public access for published posts
        if ($post->isPublished()) {
            return true;
        }

        // Admin, editor, and author can view drafts/unpublished posts
        if ($user && in_array($user->role, ['admin', 'editor', 'author'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create posts.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor', 'author']);
    }

    /**
     * Determine whether the user can update the post.
     */
    public function update(User $user, Post $post): bool
    {
        // Admin and editor can update any post
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        // Authors can only update their own posts
        if ($user->role === 'author') {
            return $user->id === $post->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the post.
     */
    public function delete(User $user, Post $post): bool
    {
        // Admin and editor can delete any post
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        // Authors can only delete their own posts
        if ($user->role === 'author') {
            return $user->id === $post->user_id;
        }

        return false;
    }
}
