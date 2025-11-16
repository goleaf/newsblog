<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrokenLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'url',
        'status',
        'checked_at',
        'response_code',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Scope: pending items (currently broken).
     */
    public function scopePending($query)
    {
        return $query->where('status', 'broken');
    }

    /**
     * Scope: fixed items (links confirmed ok).
     */
    public function scopeFixed($query)
    {
        return $query->where('status', 'ok');
    }

    /**
     * Scope: ignored items.
     */
    public function scopeIgnored($query)
    {
        return $query->where('status', 'ignored');
    }

    /**
     * Mark this link as fixed (ok) and update timestamp.
     */
    public function markAsFixed(): void
    {
        $this->update([
            'status' => 'ok',
            'checked_at' => now(),
        ]);
    }

    /**
     * Mark this link as ignored and update timestamp.
     */
    public function markAsIgnored(): void
    {
        $this->update([
            'status' => 'ignored',
            'checked_at' => now(),
        ]);
    }
}
