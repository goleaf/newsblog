<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageProcessingService
{
    /**
     * Upload entry point: delegates to processUpload and returns Media.
     */
    public function upload(UploadedFile $file, int $userId): Media
    {
        return $this->processUpload($file, $userId);
    }

    /**
     * Validate, store, generate variants, and persist a Media record.
     */
    public function processUpload(UploadedFile $file, int $userId): Media
    {
        $this->validateFile($file);

        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $nowPath = date('Y/m');
        $filename = $this->generateSafeFilename($file);
        $relativeDir = 'media/'.$nowPath;
        $relativePath = $relativeDir.'/'.$filename; // stored relative to public disk
        Storage::disk('public')->putFileAs($relativeDir, $file, $filename);
        $absolute = Storage::disk('public')->path($relativePath);

        // Strip EXIF + optimize
        $this->stripExif($absolute);
        $this->optimize($absolute, $mimeType);

        [$origW, $origH, $type] = @getimagesize($absolute) ?: [null, null, null];

        // Generate variants next to original
        $variants = $this->generateVariantsAlongside($absolute, [
            'thumbnail' => 150,
            'medium' => 300,
            'large' => 1024,
        ]);

        $media = new Media;
        $media->user_id = $userId;
        $media->file_name = $file->getClientOriginalName();
        $media->file_path = $relativePath; // relative to public disk
        $media->file_type = 'image';
        $media->file_size = is_file($absolute) ? (int) filesize($absolute) : 0;
        $media->mime_type = $mimeType;
        $media->metadata = [
            'original_dimensions' => [
                'width' => $origW,
                'height' => $origH,
            ],
            'variants' => $variants,
        ];
        $media->save();

        return $media;
    }

    /**
     * Delete media and its variants from disk and database.
     */
    public function deleteMedia(Media $media): void
    {
        // Delete original
        Storage::disk('public')->delete($media->file_path);

        // Delete variants if metadata present
        $variants = $media->metadata['variants'] ?? [];
        foreach ($variants as $variant) {
            if (! empty($variant['path'])) {
                Storage::disk('public')->delete($variant['path']);
            }
            if (! empty($variant['webp'])) {
                Storage::disk('public')->delete($variant['webp']);
            }
        }

        $media->delete();
    }

    /**
     * Validate allowed mime types and max size (10MB).
     */
    protected function validateFile(UploadedFile $file): void
    {
        $mime = (string) ($file->getMimeType() ?? $file->getClientMimeType() ?? '');
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (! in_array(strtolower($mime), $allowed, true) && ! str_starts_with(strtolower($mime), 'image/')) {
            throw new \InvalidArgumentException('Invalid file type');
        }

        $size = $file->getSize();
        if ($size === null && property_exists($file, 'size')) {
            $size = (int) $file->size;
        }
        if ($size !== null && $size > 10 * 1024 * 1024) {
            throw new \InvalidArgumentException('File size exceeds 10MB limit');
        }
    }

    /**
     * Generate variants beside original and return metadata array.
     *
     * @param  array<string,int>  $sizes
     * @return array<string,array{path:string,webp:?string,width:int,height:int}>
     */
    protected function generateVariantsAlongside(string $absoluteOriginal, array $sizes): array
    {
        $results = [];
        [$width, $height, $type] = @getimagesize($absoluteOriginal) ?: [null, null, null];
        $srcImage = $this->createImageResource($absoluteOriginal, $type);
        $pathInfo = pathinfo($absoluteOriginal);
        $dirAbs = $pathInfo['dirname'];
        $dirRel = ltrim(str_replace(Storage::disk('public')->path(''), '', $dirAbs), DIRECTORY_SEPARATOR);

        foreach ($sizes as $label => $maxWidth) {
            $variantFilename = $pathInfo['filename'].'_'.$label.'.jpg';
            $variantAbs = $dirAbs.DIRECTORY_SEPARATOR.$variantFilename;

            $variantW = (int) $maxWidth;
            $variantH = 0;

            if ($srcImage && $width && $height) {
                $scale = $maxWidth / max(1, $width);
                $scaledH = (int) round(max(1, $height) * $scale);
                $variantH = $scaledH;
                $dst = imagecreatetruecolor($variantW, max(1, $scaledH));
                imagecopyresampled($dst, $srcImage, 0, 0, 0, 0, $variantW, max(1, $scaledH), max(1, $width), max(1, $height));
                $this->saveImageResource($dst, $variantAbs, IMAGETYPE_JPEG, quality: 82);
                imagedestroy($dst);
            } else {
                @copy($absoluteOriginal, $variantAbs);
            }

            // Optimize and create webp next to variant if possible
            if (is_file($variantAbs)) {
                $this->optimize($variantAbs, 'image/jpeg');
            }
            $webpAbs = $this->toWebp($variantAbs);

            $results[$label] = [
                'path' => $dirRel.'/'.$variantFilename,
                'webp' => $webpAbs ? $this->relativeFromAbsolute($webpAbs) : null,
                'width' => $variantW,
                'height' => $variantH,
            ];
        }

        if ($srcImage) {
            imagedestroy($srcImage);
        }

        return $results;
    }

    protected function toWebp(string $absolutePath): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }
        [$w, $h] = @getimagesize($absolutePath) ?: [null, null];
        if (! $w || ! $h) {
            return null;
        }
        $src = @imagecreatefromjpeg($absolutePath) ?: null;
        if (! $src) {
            return null;
        }
        $pathInfo = pathinfo($absolutePath);
        $webpAbs = $pathInfo['dirname'].DIRECTORY_SEPARATOR.$pathInfo['filename'].'.webp';
        imagepalettetotruecolor($src);
        imagealphablending($src, true);
        imagesavealpha($src, true);
        imagewebp($src, $webpAbs, 82);
        imagedestroy($src);

        return $webpAbs;
    }

    protected function relativeFromAbsolute(string $absolute): string
    {
        $root = rtrim(Storage::disk('public')->path(''), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return ltrim(str_replace($root, '', $absolute), DIRECTORY_SEPARATOR);
    }

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
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $slug = preg_replace('/[^a-z0-9-_]+/i', '-', (string) $base) ?: 'image';

        return $slug.'-'.uniqid('', true).'.'.$ext;
    }

    private function createImageResource(string $absolutePath, ?int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absolutePath),
            IMAGETYPE_PNG => @imagecreatefrompng($absolutePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
            default => @imagecreatefromstring(@file_get_contents($absolutePath) ?: ''),
        };
    }

    private function saveImageResource($image, string $absolutePath, ?int $type, int $quality = 85): void
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                @imagejpeg($image, $absolutePath, $quality);
                break;
            case IMAGETYPE_PNG:
                $pngQuality = (int) round((100 - $quality) / 10);
                @imagepng($image, $absolutePath, max(0, min(9, $pngQuality)));
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    @imagewebp($image, $absolutePath, $quality);
                    break;
                }
                // fall-through
            default:
                @imagejpeg($image, $absolutePath, $quality);
        }
    }
}
