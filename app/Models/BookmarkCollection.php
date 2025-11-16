<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookmarkCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_public',
        'share_token',
        'order',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public static function generateShareToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class, 'collection_id')->orderBy('order');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId)->orderBy('order');
    }
}
