<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'platform', // e.g., twitter, facebook, linkedin
        'shared_at',
        'meta', // arbitrary share metadata
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shared_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
