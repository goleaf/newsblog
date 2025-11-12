<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SearchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'query',
        'result_count',
        'execution_time',
        'search_type',
        'fuzzy_enabled',
        'threshold',
        'filters',
        'ip_address',
        'user_agent',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fuzzy_enabled' => 'boolean',
            'filters' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(SearchClick::class);
    }

    public function scopeNoResults($query)
    {
        return $query->where('result_count', 0);
    }

    public function scopeRecent($query, string $period = 'day')
    {
        $date = match ($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => now()->subDay(),
        };

        return $query->where('created_at', '>=', $date);
    }
}
