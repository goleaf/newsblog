<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrokenLink extends Model
{
    protected $fillable = [
        'post_id',
        'url',
        'status_code',
        'error_message',
        'last_checked_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFixed($query)
    {
        return $query->where('status', 'fixed');
    }

    public function scopeIgnored($query)
    {
        return $query->where('status', 'ignored');
    }

    public function markAsFixed(): void
    {
        $this->update(['status' => 'fixed']);
    }

    public function markAsIgnored(): void
    {
        $this->update(['status' => 'ignored']);
    }
}
