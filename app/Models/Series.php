<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)
            ->orderBy('order_in_series');
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
