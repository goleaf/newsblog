<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media_library';

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'alt_text',
        'title',
        'caption',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute()
    {
        return asset('storage/'.$this->file_path);
    }

    public function getSizeHumanReadableAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get URL for a specific image variant
     */
    public function getVariantUrl(string $variant = 'original', bool $webp = false): string
    {
        if ($variant === 'original' || $this->file_type !== 'image') {
            return $this->url;
        }

        $pathInfo = pathinfo($this->file_path);
        $baseFilename = $pathInfo['filename'];
        $directory = $pathInfo['dirname'];

        $extension = $webp ? 'webp' : 'jpg';
        $variantFilename = "{$baseFilename}_{$variant}.{$extension}";
        $variantPath = "{$directory}/{$variantFilename}";

        return asset('storage/'.$variantPath);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrlAttribute(): string
    {
        return $this->getVariantUrl('thumbnail');
    }

    /**
     * Get medium size URL
     */
    public function getMediumUrlAttribute(): string
    {
        return $this->getVariantUrl('medium');
    }

    /**
     * Get large size URL
     */
    public function getLargeUrlAttribute(): string
    {
        return $this->getVariantUrl('large');
    }

    public function scopeImages($query)
    {
        return $query->whereIn('file_type', ['image']);
    }

    public function scopeDocuments($query)
    {
        return $query->whereIn('file_type', ['document']);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
