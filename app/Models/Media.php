<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media_library';

    protected $fillable = [
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'alt_text',
        'caption',
        'title',
        'metadata',
        'user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'file_size' => 'int',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor: human readable size (e.g. 1.2 MB)
     */
    public function getSizeHumanReadableAttribute(): ?string
    {
        $size = (int) ($this->file_size ?? 0);
        if ($size <= 0) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = (int) floor(log($size, 1024));
        $power = max(0, min($power, count($units) - 1));
        $value = $size / pow(1024, $power);

        return number_format($value, 1).' '.$units[$power];
    }

    /**
     * Accessor: thumbnail URL, prefer metadata variant, fallback to original.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        $thumb = $this->metadata['variants']['thumbnail']['path'] ?? null;
        if ($thumb) {
            return asset('storage/'.ltrim((string) $thumb, 'public/'));
        }

        $path = $this->file_path ? (string) $this->file_path : null;

        return $path ? asset('storage/'.ltrim($path, 'public/')) : null;
    }

    /**
     * Scope images only.
     */
    public function scopeImages($query)
    {
        return $query->where('file_type', 'image');
    }
}
