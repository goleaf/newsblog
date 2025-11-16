<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    public function __construct(
        protected ImageProcessingService $imageProcessingService
    ) {}

    /**
     * Upload and process an image file, creating a Media record.
     */
    public function upload(UploadedFile $file, ?int $userId = null, ?string $altText = null, ?string $caption = null): Media
    {
        // Upload and get file details
        $uploadData = $this->imageProcessingService->upload($file);

        // Get absolute path for variant generation
        $absolutePath = Storage::path($uploadData['path']);

        // Generate responsive variants
        $variants = $this->imageProcessingService->generateVariants($absolutePath);

        // Convert to WebP
        $webp = $this->imageProcessingService->convertToWebP($absolutePath);

        // Merge metadata
        $metadata = array_merge($uploadData['metadata'] ?? [], [
            'variants' => $variants,
            'webp' => $webp,
        ]);

        // Determine file type from mime type
        $fileType = str_starts_with($uploadData['mime_type'], 'image/') ? 'image' : 'file';

        // Remove 'public/' prefix from path for storage in database
        // The path is stored relative to the public disk
        $relativePath = str_replace('public/', '', $uploadData['path']);

        // Create Media record
        return Media::create([
            'file_name' => $uploadData['filename'],
            'file_path' => $relativePath,
            'file_type' => $fileType,
            'mime_type' => $uploadData['mime_type'],
            'file_size' => $uploadData['size'],
            'alt_text' => $altText,
            'caption' => $caption,
            'metadata' => $metadata,
            'user_id' => $userId,
        ]);
    }

    /**
     * Delete a media item and all its associated files.
     */
    public function delete(Media $media): bool
    {
        // Delete original file
        if ($media->file_path) {
            $originalRelative = str_replace('public/', '', $media->file_path);
            if (Storage::disk('public')->exists($originalRelative)) {
                Storage::disk('public')->delete($originalRelative);
            }
        }

        // Delete variants
        $variants = $media->metadata['variants'] ?? [];
        foreach ($variants as $variantPath) {
            if (is_string($variantPath)) {
                $variantRelative = str_replace('public/', '', $variantPath);
                if (Storage::disk('public')->exists($variantRelative)) {
                    Storage::disk('public')->delete($variantRelative);
                }
            }
        }

        // Delete WebP version
        $webpPath = $media->metadata['webp'] ?? null;
        if (is_string($webpPath)) {
            $webpRelative = str_replace('public/', '', $webpPath);
            if (Storage::disk('public')->exists($webpRelative)) {
                Storage::disk('public')->delete($webpRelative);
            }
        }

        // Delete the database record
        return $media->delete();
    }

    /**
     * Get user's uploaded media with pagination.
     */
    public function getUserMedia(int $userId, int $perPage = 24)
    {
        return Media::where('user_id', $userId)
            ->images()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Validate image file.
     */
    public function validateImage(UploadedFile $file, int $maxSizeKb = 10240, ?array $dimensions = null): bool
    {
        // Check if it's an image
        if (! $file->isValid() || ! str_starts_with($file->getMimeType() ?? '', 'image/')) {
            return false;
        }

        // Check file size
        if ($file->getSize() > $maxSizeKb * 1024) {
            return false;
        }

        // Check dimensions if specified
        if ($dimensions) {
            [$width, $height] = getimagesize($file->getRealPath());

            if (isset($dimensions['max_width']) && $width > $dimensions['max_width']) {
                return false;
            }

            if (isset($dimensions['max_height']) && $height > $dimensions['max_height']) {
                return false;
            }

            if (isset($dimensions['min_width']) && $width < $dimensions['min_width']) {
                return false;
            }

            if (isset($dimensions['min_height']) && $height < $dimensions['min_height']) {
                return false;
            }
        }

        return true;
    }
}
