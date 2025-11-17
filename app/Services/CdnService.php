<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class CdnService
{
    /**
     * Get the CDN URL for an asset.
     */
    public function assetUrl(string $path): string
    {
        $cdnUrl = config('app.cdn_url');

        if (! $cdnUrl) {
            return asset($path);
        }

        return rtrim($cdnUrl, '/').'/'.$path;
    }

    /**
     * Get the CDN URL for a storage file.
     */
    public function storageUrl(string $path, string $disk = 'public'): string
    {
        $cdnUrl = config('app.cdn_url');

        if (! $cdnUrl) {
            return Storage::disk($disk)->url($path);
        }

        // Remove 'public/' prefix if present
        $path = str_replace('public/', '', $path);

        return rtrim($cdnUrl, '/').'/storage/'.$path;
    }

    /**
     * Upload file to S3 with CloudFront distribution.
     */
    public function uploadToS3(string $localPath, string $s3Path, array $options = []): bool
    {
        if (! config('filesystems.disks.s3.bucket')) {
            return false;
        }

        $defaultOptions = [
            'visibility' => 'public',
            'CacheControl' => 'public, max-age=31536000, immutable',
        ];

        $options = array_merge($defaultOptions, $options);

        return Storage::disk('s3')->put($s3Path, file_get_contents($localPath), $options);
    }

    /**
     * Sync local storage to S3.
     */
    public function syncToS3(string $localDirectory = 'public', string $s3Directory = ''): array
    {
        $synced = [];
        $failed = [];

        if (! config('filesystems.disks.s3.bucket')) {
            return ['synced' => $synced, 'failed' => ['S3 not configured']];
        }

        $files = Storage::disk('public')->allFiles($localDirectory);

        foreach ($files as $file) {
            $s3Path = $s3Directory ? $s3Directory.'/'.$file : $file;

            try {
                $content = Storage::disk('public')->get($file);
                $mimeType = Storage::disk('public')->mimeType($file);

                $options = [
                    'visibility' => 'public',
                    'CacheControl' => $this->getCacheControl($file),
                    'ContentType' => $mimeType,
                ];

                if (Storage::disk('s3')->put($s3Path, $content, $options)) {
                    $synced[] = $file;
                } else {
                    $failed[] = $file;
                }
            } catch (\Exception $e) {
                $failed[] = $file.' ('.$e->getMessage().')';
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }

    /**
     * Get appropriate cache control header based on file type.
     */
    protected function getCacheControl(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // Long cache for immutable assets
        $immutableExtensions = ['css', 'js', 'woff', 'woff2', 'ttf', 'eot'];
        if (in_array($extension, $immutableExtensions)) {
            return 'public, max-age=31536000, immutable';
        }

        // Medium cache for images
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];
        if (in_array($extension, $imageExtensions)) {
            return 'public, max-age=2592000'; // 30 days
        }

        // Default cache
        return 'public, max-age=86400'; // 1 day
    }

    /**
     * Invalidate CloudFront cache for specific paths.
     */
    public function invalidateCache(array $paths): bool
    {
        // This would require AWS SDK for CloudFront invalidation
        // For now, return true as a placeholder
        // In production, implement CloudFront invalidation API call
        return true;
    }

    /**
     * Check if CDN is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('app.cdn_url'));
    }

    /**
     * Check if S3 is configured.
     */
    public function isS3Configured(): bool
    {
        return ! empty(config('filesystems.disks.s3.bucket'));
    }
}
