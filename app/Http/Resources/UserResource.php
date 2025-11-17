<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * This resource transforms user data for API responses.
     * - Hides sensitive information (email, password, etc.)
     * - Includes public profile data
     * - Conditionally includes private data for authenticated user
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isOwnProfile = $request->user() && $request->user()->id === $this->id;

        $data = [
            // Basic public information
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_url,
            'bio' => $this->bio,
            'role' => $this->role?->value,
            'created_at' => $this->created_at?->toIso8601String(),

            // Profile information - conditionally loaded
            'profile' => $this->when(
                $this->relationLoaded('profile') && $this->profile,
                function () {
                    return [
                        'website' => $this->profile->website,
                        'twitter_handle' => $this->profile->twitter_handle,
                        'github_username' => $this->profile->github_username,
                        'linkedin_url' => $this->profile->linkedin_url,
                        'location' => $this->profile->location,
                        'company' => $this->profile->company,
                        'job_title' => $this->profile->job_title,
                    ];
                }
            ),

            // Preferences - only for own profile
            'preferences' => $this->when(
                $isOwnProfile && $this->relationLoaded('preferences') && $this->preferences,
                function () {
                    return [
                        'email_notifications' => $this->preferences->email_notifications,
                        'comment_notifications' => $this->preferences->comment_notifications,
                        'newsletter_frequency' => $this->preferences->newsletter_frequency,
                        'theme' => $this->preferences->theme,
                        'reading_list_public' => $this->preferences->reading_list_public,
                        'profile_visibility' => $this->preferences->profile_visibility,
                    ];
                }
            ),

            // Statistics - conditionally loaded
            'posts_count' => $this->when(
                isset($this->posts_count),
                fn () => $this->posts_count
            ),
            'comments_count' => $this->when(
                isset($this->comments_count),
                fn () => $this->comments_count
            ),
            'followers_count' => $this->when(
                isset($this->followers_count),
                fn () => $this->followers_count
            ),
            'following_count' => $this->when(
                isset($this->following_count),
                fn () => $this->following_count
            ),
        ];

        // Sensitive information - only for own profile (omit keys entirely otherwise)
        if ($isOwnProfile) {
            $data['email'] = $this->email;
            $data['email_verified_at'] = $this->email_verified_at?->toIso8601String();
            $data['status'] = $this->status?->value;
        }

        return $data;
    }
}
