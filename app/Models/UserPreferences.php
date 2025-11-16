<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreferences extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_notifications',
        'push_notifications',
        'theme',
        'language',
        'data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'data' => 'array',
        ];
    }

    /**
     * Get preferences data (alias for data column).
     */
    public function getPreferencesAttribute(): array
    {
        return $this->data ?? [];
    }

    /**
     * Set preferences data (alias for data column).
     */
    public function setPreferencesAttribute(array $value): void
    {
        $this->data = $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
