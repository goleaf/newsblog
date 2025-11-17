<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserService
{
    /**
     * Get user by ID with caching.
     */
    public function getUserById(int $id): ?User
    {
        $cacheKey = "user.id.{$id}";
        $cacheTtl = config('cache.ttl.users', 1800);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($id) {
            return User::with(['profile', 'preferences'])
                ->find($id);
        });
    }

    /**
     * Get user profile with caching.
     */
    public function getUserProfile(int $userId): ?User
    {
        $cacheKey = "user.profile.{$userId}";
        $cacheTtl = config('cache.ttl.users', 1800);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($userId) {
            return User::with([
                'profile',
                'preferences',
                'posts' => function ($query) {
                    $query->published()->latest()->limit(5);
                },
            ])
                ->withCount(['posts', 'comments', 'followers', 'following'])
                ->find($userId);
        });
    }

    /**
     * Get user by email with caching.
     */
    public function getUserByEmail(string $email): ?User
    {
        $cacheKey = 'user.email.'.md5($email);
        $cacheTtl = config('cache.ttl.users', 1800);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($email) {
            return User::where('email', $email)->first();
        });
    }

    /**
     * Get active authors with caching.
     */
    public function getActiveAuthors(int $limit = 10)
    {
        $cacheKey = "users.active_authors.limit{$limit}";
        $cacheTtl = config('cache.ttl.users', 1800);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($limit) {
            return User::whereIn('role', ['author', 'admin'])
                ->where('status', 'active')
                ->withCount(['posts' => function ($query) {
                    $query->published();
                }])
                ->having('posts_count', '>', 0)
                ->orderBy('posts_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Invalidate user cache.
     */
    public function invalidateUserCache(User $user): void
    {
        Cache::forget("user.id.{$user->id}");
        Cache::forget("user.profile.{$user->id}");
        Cache::forget('user.email.'.md5($user->email));
    }

    /**
     * Invalidate active authors cache.
     */
    public function invalidateActiveAuthorsCache(): void
    {
        for ($i = 5; $i <= 20; $i += 5) {
            Cache::forget("users.active_authors.limit{$i}");
        }
    }
}
