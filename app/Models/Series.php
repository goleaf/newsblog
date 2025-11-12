<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Series extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the posts in this series.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_series')
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('post_series.order');
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($series) {
            if (empty($series->slug)) {
                $series->slug = Str::slug($series->name);
            }
        });

        static::updating(function ($series) {
            if ($series->isDirty('name') && empty($series->slug)) {
                $series->slug = Str::slug($series->name);
            }
        });
    }
}
