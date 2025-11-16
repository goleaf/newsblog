<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'moderation_queue_id',
        'action', // approve, reject, escalate
        'reason',
        'performed_by',
        'performed_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(ModerationQueue::class, 'moderation_queue_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
