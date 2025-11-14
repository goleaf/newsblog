<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngagementMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'session_id',
        'user_id',
        'time_on_page',
        'scroll_depth',
        'clicked_bookmark',
        'clicked_share',
        'clicked_reaction',
        'clicked_comment',
        'clicked_related_post',
        'ip_address',
        'user_agent',
        'referer',
    ];

    protected function casts(): array
    {
        return [
            'clicked_bookmark' => 'boolean',
            'clicked_share' => 'boolean',
            'clicked_reaction' => 'boolean',
            'clicked_comment' => 'boolean',
            'clicked_related_post' => 'boolean',
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
