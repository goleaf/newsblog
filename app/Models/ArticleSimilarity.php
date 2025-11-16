<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleSimilarity extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'similar_post_id',
        'score',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'float',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function similarPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'similar_post_id');
    }
}
