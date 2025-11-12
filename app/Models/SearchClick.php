<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'search_log_id',
        'post_id',
        'position',
    ];

    public function searchLog(): BelongsTo
    {
        return $this->belongsTo(SearchLog::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
