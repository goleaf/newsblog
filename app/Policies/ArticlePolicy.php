<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticlePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any articles.
     * Requirements: 1.3, 1.4, 16.3
     */
    public function viewAny(User $user): bool
    {
        // Authors, editors, and admins can view the article management interface
        return in_array($user->role, [UserRole::Author, UserRole::Editor, UserRole::Admin]);
    }

    /**
     * Determine whether the user can view the article.
     * Requirements: 1.3, 1.4, 16.3
     */
    public function view(?User $user, Post|Article $article): bool
    {
        // Published articles are publicly accessible
        if ($article->isPublished()) {
            return true;
        }

        // Unauthenticated users cannot view unpublished articles
        if (! $user) {
            return false;
        }

        // Admins and editors can view any article
        if (in_array($user->role, [UserRole::Admin, UserRole::Editor])) {
            return true;
        }

        // Authors can view their own unpublished articles
        if ($user->role === UserRole::Author && $user->id === $article->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create articles.
     * Requirements: 1.3, 1.4, 16.3
     */
    public function create(User $user): bool
    {
        // Authors, editors, and admins can create articles
        return in_array($user->role, [UserRole::Author, UserRole::Editor, UserRole::Admin]);
    }

    /**
     * Determine whether the user can update the article.
     * Requirements: 1.3, 1.4, 16.3
     */
    public function update(User $user, Post|Article $article): bool
    {
        // Admins and editors can update any article
        if (in_array($user->role, [UserRole::Admin, UserRole::Editor])) {
            return true;
        }

        // Authors can only update their own articles
        if ($user->role === UserRole::Author && $user->id === $article->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the article.
     * Requirements: 1.3, 1.4, 16.3
     */
    public function delete(User $user, Post|Article $article): bool
    {
        // Admins and editors can delete any article
        if (in_array($user->role, [UserRole::Admin, UserRole::Editor])) {
            return true;
        }

        // Authors can only delete their own articles
        if ($user->role === UserRole::Author && $user->id === $article->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can publish the article.
     * Requirements: 1.3, 1.4, 16.3
     */
    public function publish(User $user, Post|Article $article): bool
    {
        // Only editors and admins can publish/unpublish articles
        return in_array($user->role, [UserRole::Editor, UserRole::Admin]);
    }

    /**
     * Determine whether the user can restore the article.
     * Requirements: 1.3, 1.4, 16.3
     */
    public function restore(User $user, Post|Article $article): bool
    {
        // Admins and editors can restore deleted articles
        return in_array($user->role, [UserRole::Admin, UserRole::Editor]);
    }

    /**
     * Determine whether the user can permanently delete the article.
     * Requirements: 1.3, 1.4, 16.3
     */
    public function forceDelete(User $user, Post|Article $article): bool
    {
        // Only admins can permanently delete articles
        return $user->role === UserRole::Admin;
    }
}
