<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'source', // e.g., direct, search, social, referral
        'medium', // e.g., organic, cpc
        'campaign',
        'referer_url',
        'landing_url',
        'utm', // JSON of UTM params
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'utm' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
