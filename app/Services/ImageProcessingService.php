<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageProcessingService
{
    private array $sizes = [
        'thumbnail' => [150, 150],
        'medium' => [300, 300],
        'large' => [1024, 1024],
    ];

    /**
     * Process an uploaded image file
     */
    public function processUpload(UploadedFile $file, int $userId): Media
    {
        // Validate file
        $this->validateFile($file);

        // Generate unique filename
        $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $basePath = 'media/'.date('Y/m');

        // Store original file
        $originalPath = $file->storeAs($basePath, $filename, 'public');

        // Get full storage path
        $fullPath = Storage::disk('public')->path($originalPath);

        // Strip EXIF metadata from original
        $this->stripExif($fullPath);

        // Generate image variants
        $variants = $this->generateVariants($fullPath, $basePath, pathinfo($filename, PATHINFO_FILENAME));

        // Create media record with variant metadata
        $media = Media::create([
            'user_id' => $userId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $originalPath,
            'file_type' => 'image',
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'metadata' => [
                'variants' => $variants,
                'original_dimensions' => [
                    'width' => Image::read($fullPath)->width(),
                    'height' => Image::read($fullPath)->height(),
                ],
            ],
        ]);

        return $media;
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file): void
    {
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        if (! in_array($file->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
        }

        if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
            throw new \InvalidArgumentException('File size exceeds 10MB limit.');
        }
    }

    /**
     * Generate image variants (thumbnail, medium, large)
     */
    private function generateVariants(string $originalPath, string $basePath, string $baseFilename): array
    {
        $variants = [];

        foreach ($this->sizes as $name => [$width, $height]) {
            $image = Image::read($originalPath);

            // Resize maintaining aspect ratio
            $image->scale(width: $width, height: $height);

            // Compress with 85% quality
            $variantFilename = "{$baseFilename}_{$name}.jpg";
            $variantPath = Storage::disk('public')->path("{$basePath}/{$variantFilename}");

            // Save as JPEG with 85% quality
            $image->toJpeg(quality: 85)->save($variantPath);

            // Generate WebP version
            $webpFilename = "{$baseFilename}_{$name}.webp";
            $webpPath = Storage::disk('public')->path("{$basePath}/{$webpFilename}");
            $image->toWebp(quality: 85)->save($webpPath);

            $variants[$name] = [
                'path' => "{$basePath}/{$variantFilename}",
                'webp_path' => "{$basePath}/{$webpFilename}",
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        }

        return $variants;
    }

    /**
     * Strip EXIF metadata from image
     */
    private function stripExif(string $path): void
    {
        // Read and re-save the image to strip EXIF data
        $image = Image::read($path);
        $image->save($path);
    }

    /**
     * Get URL for a specific image variant
     */
    public function getVariantUrl(Media $media, string $variant = 'original', bool $webp = false): string
    {
        if ($variant === 'original') {
            return asset('storage/'.$media->file_path);
        }

        // Use metadata if available
        if ($media->metadata && isset($media->metadata['variants'][$variant])) {
            $path = $webp
                ? $media->metadata['variants'][$variant]['webp_path']
                : $media->metadata['variants'][$variant]['path'];

            return asset('storage/'.$path);
        }

        // Fallback to naming convention for legacy media
        $pathInfo = pathinfo($media->file_path);
        $baseFilename = $pathInfo['filename'];
        $directory = $pathInfo['dirname'];

        $extension = $webp ? 'webp' : 'jpg';
        $variantFilename = "{$baseFilename}_{$variant}.{$extension}";
        $variantPath = "{$directory}/{$variantFilename}";

        return asset('storage/'.$variantPath);
    }

    /**
     * Delete media file and all its variants
     */
    public function deleteMedia(Media $media): void
    {
        // Delete original file
        Storage::disk('public')->delete($media->file_path);

        // Delete variants
        $pathInfo = pathinfo($media->file_path);
        $baseFilename = $pathInfo['filename'];
        $directory = $pathInfo['dirname'];

        foreach (array_keys($this->sizes) as $variant) {
            Storage::disk('public')->delete("{$directory}/{$baseFilename}_{$variant}.jpg");
            Storage::disk('public')->delete("{$directory}/{$baseFilename}_{$variant}.webp");
        }
    }

    /**
     * Check if a file is an image
     */
    public function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }
}
