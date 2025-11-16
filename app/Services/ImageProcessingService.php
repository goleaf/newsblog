<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageProcessingService
{
    /**
     * Validates and stores the original upload.
     *
     * @return array{path:string, filename:string, mime_type:string, size:int, metadata:array<string, mixed>}
     */
    public function upload(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        if (! str_starts_with($mimeType, 'image/')) {
            throw new \InvalidArgumentException('Only image uploads are supported.');
        }

        $filename = $this->generateSafeFilename($file);
        $storedRelative = 'media/'.$filename;
        Storage::disk('public')->putFileAs('media', $file, $filename);
        $storedPath = 'public/'.$storedRelative;
        $absolute = Storage::disk('public')->path($storedRelative);

        // Strip EXIF and optimize the original
        $this->stripExif($absolute);
        $this->optimize($absolute, $mimeType);

        [$width, $height] = @getimagesize($absolute) ?: [null, null];

        return [
            'path' => $storedPath,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => (int) filesize($absolute),
            'metadata' => array_filter([
                'width' => $width,
                'height' => $height,
            ], static fn ($v) => $v !== null),
        ];
    }

    /**
     * Generates thumbnail, medium, and large variants.
     *
     * @param  array<string,int>  $sizes
     * @return array<string,string> sizeName => stored path
     */
    public function generateVariants(string $absolutePath, array $sizes = ['thumbnail' => 200, 'medium' => 800, 'large' => 1600]): array
    {
        $results = [];
        [$width, $height, $type] = @getimagesize($absolutePath) ?: [null, null, null];

        $srcImage = $this->createImageResource($absolutePath, $type);
        $pathInfo = pathinfo($absolutePath);

        foreach ($sizes as $label => $targetWidth) {
            $variantFilename = $pathInfo['filename'].'_'.$label.'.'.strtolower($pathInfo['extension'] ?? 'jpg');
            $storageRelative = 'media/variants/'.$variantFilename;
            $variantAbsolute = Storage::disk('public')->path($storageRelative);
            @mkdir(dirname($variantAbsolute), 0777, true);

            if ($srcImage && $width && $height) {
                $scale = $targetWidth / max(1, $width);
                $targetHeight = (int) round(max(1, $height) * $scale);
                $dst = imagecreatetruecolor($targetWidth, max(1, $targetHeight));
                imagecopyresampled($dst, $srcImage, 0, 0, 0, 0, $targetWidth, max(1, $targetHeight), max(1, $width), max(1, $height));
                $this->saveImageResource($dst, $variantAbsolute, $type);
                imagedestroy($dst);
            } else {
                // Fallback: if GD not available, copy original
				if (! @copy($absolutePath, $variantAbsolute)) {
					@file_put_contents($variantAbsolute, @file_get_contents($absolutePath) ?: '');
				}
            }

            if (is_file($variantAbsolute)) {
                $this->optimize($variantAbsolute, 'image/jpeg');
            }
            $results[$label] = 'public/'.$storageRelative;
        }

        if ($srcImage) {
            imagedestroy($srcImage);
        }

        return $results;
    }

    /**
     * Lossy compression tuned per type, using GD re-encode.
     */
    public function optimize(string $absolutePath, string $mimeType): void
    {
        [$width, $height, $type] = @getimagesize($absolutePath) ?: [null, null, null];
        if (! $width || ! $height) {
            return;
        }

        $image = $this->createImageResource($absolutePath, $type);
        if (! $image) {
            return;
        }

        $this->saveImageResource($image, $absolutePath, $type, quality: 82);
        imagedestroy($image);
    }

    /**
     * Convert to WebP if possible, keep original as fallback.
     *
     * @return string|null Stored public path to WebP, or null if conversion failed.
     */
    public function convertToWebP(string $absolutePath): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        [$width, $height, $type] = @getimagesize($absolutePath) ?: [null, null, null];
        if (! $width || ! $height) {
            return null;
        }

        $src = $this->createImageResource($absolutePath, $type);
        if (! $src) {
            return null;
        }

        $pathInfo = pathinfo($absolutePath);
        $webpRelative = 'media/'.$pathInfo['filename'].'.webp';
        $webpAbsolute = Storage::disk('public')->path($webpRelative);

        imagepalettetotruecolor($src);
        imagealphablending($src, true);
        imagesavealpha($src, true);
        imagewebp($src, $webpAbsolute, 82);
        imagedestroy($src);

        return 'public/'.$webpRelative;
    }

    /**
     * Strip EXIF by re-encoding.
     */
    public function stripExif(string $absolutePath): void
    {
        [$width, $height, $type] = @getimagesize($absolutePath) ?: [null, null, null];
        if (! $width || ! $height) {
            return;
        }

        $img = $this->createImageResource($absolutePath, $type);
        if (! $img) {
            return;
        }

        $this->saveImageResource($img, $absolutePath, $type, quality: 85);
        imagedestroy($img);
    }

    private function generateSafeFilename(UploadedFile $file): string
    {
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $slug = preg_replace('/[^a-z0-9-_]+/i', '-', (string) $base) ?: 'file';

        return $slug.'-'.uniqid('', true).'.'.$ext;
    }

    /**
     * @param  int|null  $type  One of the IMAGETYPE_* constants
     * @return resource|\GdImage|null
     */
    private function createImageResource(string $absolutePath, ?int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absolutePath),
            IMAGETYPE_PNG => @imagecreatefrompng($absolutePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
            default => @imagecreatefromstring(@file_get_contents($absolutePath) ?: ''),
        };
    }

    /**
     * @param  resource|\GdImage  $image
     */
    private function saveImageResource($image, string $absolutePath, ?int $type, int $quality = 85): void
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                @imagejpeg($image, $absolutePath, $quality);
                break;
            case IMAGETYPE_PNG:
                // Convert quality [0,9] where lower is better compression
                $pngQuality = (int) round((100 - $quality) / 10);
                @imagepng($image, $absolutePath, max(0, min(9, $pngQuality)));
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    @imagewebp($image, $absolutePath, $quality);
                    break;
                }
                // fall-through to default if webp unsupported
            default:
                @imagejpeg($image, $absolutePath, $quality);
        }
    }
}
