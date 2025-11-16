<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AvatarUploadService
{
    public function __construct(
        protected ImageProcessingService $imageService
    ) {}

    /**
     * Upload and process avatar image.
     * Resizes to 200x200 pixels, optimizes file size, and uploads to storage.
     *
     * @param  UploadedFile  $file  The uploaded avatar image
     * @param  string|null  $oldAvatarPath  Path to old avatar to delete
     * @return string The stored avatar path
     */
    public function upload(UploadedFile $file, ?string $oldAvatarPath = null): string
    {
        // Validate image
        $this->validateImage($file);

        // Delete old avatar if exists
        if ($oldAvatarPath && Storage::disk('public')->exists($oldAvatarPath)) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        // Upload original image
        $uploadResult = $this->imageService->upload($file);
        $absolutePath = Storage::disk('public')->path(str_replace('public/', '', $uploadResult['path']));

        // Resize to 200x200 pixels
        $this->resizeAvatar($absolutePath, 200, 200);

        // Optimize file size
        $this->imageService->optimize($absolutePath, $uploadResult['mime_type']);

        // Return the relative path for storage
        return str_replace('public/', '', $uploadResult['path']);
    }

    /**
     * Validate the uploaded image file.
     *
     * @throws \InvalidArgumentException
     */
    protected function validateImage(UploadedFile $file): void
    {
        // Check file size (max 2MB)
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new \InvalidArgumentException('Avatar image must not exceed 2MB.');
        }

        // Check mime type
        $mimeType = $file->getMimeType();
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];

        if (! in_array($mimeType, $allowedMimes)) {
            throw new \InvalidArgumentException('Avatar must be a JPEG, PNG, or GIF image.');
        }

        // Check image dimensions
        $imageInfo = @getimagesize($file->getRealPath());
        if (! $imageInfo) {
            throw new \InvalidArgumentException('Invalid image file.');
        }

        [$width, $height] = $imageInfo;

        // Minimum dimensions check
        if ($width < 100 || $height < 100) {
            throw new \InvalidArgumentException('Avatar image must be at least 100x100 pixels.');
        }
    }

    /**
     * Resize avatar to specified dimensions.
     */
    protected function resizeAvatar(string $absolutePath, int $width, int $height): void
    {
        $imageInfo = @getimagesize($absolutePath);
        if (! $imageInfo) {
            return;
        }

        [$srcWidth, $srcHeight, $type] = $imageInfo;

        // Create image resource from file
        $srcImage = $this->createImageResource($absolutePath, $type);
        if (! $srcImage) {
            return;
        }

        // Calculate dimensions for square crop
        $size = min($srcWidth, $srcHeight);
        $srcX = ($srcWidth - $size) / 2;
        $srcY = ($srcHeight - $size) / 2;

        // Create destination image
        $dstImage = imagecreatetruecolor($width, $height);

        // Preserve transparency for PNG and GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
            imagefilledrectangle($dstImage, 0, 0, $width, $height, $transparent);
        }

        // Resize and crop to square
        imagecopyresampled(
            $dstImage,
            $srcImage,
            0,
            0,
            (int) $srcX,
            (int) $srcY,
            $width,
            $height,
            (int) $size,
            (int) $size
        );

        // Save the resized image
        $this->saveImageResource($dstImage, $absolutePath, $type);

        // Clean up
        imagedestroy($srcImage);
        imagedestroy($dstImage);
    }

    /**
     * Create image resource from file.
     *
     * @return resource|\GdImage|null
     */
    protected function createImageResource(string $absolutePath, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absolutePath),
            IMAGETYPE_PNG => @imagecreatefrompng($absolutePath),
            IMAGETYPE_GIF => @imagecreatefromgif($absolutePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
            default => null,
        };
    }

    /**
     * Save image resource to file.
     *
     * @param  resource|\GdImage  $image
     */
    protected function saveImageResource($image, string $absolutePath, int $type, int $quality = 90): void
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                @imagejpeg($image, $absolutePath, $quality);
                break;
            case IMAGETYPE_PNG:
                $pngQuality = (int) round((100 - $quality) / 10);
                @imagepng($image, $absolutePath, max(0, min(9, $pngQuality)));
                break;
            case IMAGETYPE_GIF:
                @imagegif($image, $absolutePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    @imagewebp($image, $absolutePath, $quality);
                }
                break;
        }
    }
}
