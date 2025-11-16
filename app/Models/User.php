<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'bio',
        'status',
        'email_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_preferences' => 'array',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Get default email preferences.
     */
    public function getDefaultEmailPreferences(): array
    {
        return [
            'comment_replies' => true,
            'post_published' => true,
            'comment_approved' => true,
            'series_updated' => true,
            'newsletter' => true,
            'frequency' => 'immediate', // immediate, daily, weekly
        ];
    }

    /**
     * Get email preferences with defaults.
     */
    public function getEmailPreferences(): array
    {
        return array_merge(
            $this->getDefaultEmailPreferences(),
            $this->email_preferences ?? []
        );
    }

    /**
     * Update email preferences.
     */
    public function updateEmailPreferences(array $preferences): void
    {
        $this->update([
            'email_preferences' => array_merge(
                $this->getEmailPreferences(),
                $preferences
            ),
        ]);
    }

    /**
     * Check if user wants email notifications for a specific type.
     */
    public function wantsEmailNotification(string $type): bool
    {
        $preferences = $this->getEmailPreferences();

        return $preferences[$type] ?? false;
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function bookmarkCollections()
    {
        return $this->hasMany(BookmarkCollection::class)->orderBy('order');
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function postViews()
    {
        return $this->hasMany(PostView::class)->latest('viewed_at');
    }

    public function scopeActive($query)
    {
        return $query->where('status', UserStatus::Active);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', UserRole::Admin);
    }

    public function scopeEditors($query)
    {
        return $query->where('role', UserRole::Editor);
    }

    public function scopeAuthors($query)
    {
        return $query->where('role', UserRole::Author);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isEditor(): bool
    {
        return $this->role === UserRole::Editor;
    }

    public function isAuthor(): bool
    {
        return $this->role === UserRole::Author;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=random';
    }
}
