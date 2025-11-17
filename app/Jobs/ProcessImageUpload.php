<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\ImageProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessImageUpload implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $mediaId
    ) {
        $this->onQueue('low');
    }

    /**
     * Execute the job.
     */
    public function handle(ImageProcessingService $imageProcessingService): void
    {
        $media = Media::find($this->mediaId);

        if (! $media) {
            Log::warning('Media not found for image processing', ['media_id' => $this->mediaId]);

            return;
        }

        // Get absolute path for variant generation
        $absolutePath = Storage::path('public/'.$media->file_path);

        if (! file_exists($absolutePath)) {
            Log::error('Image file not found for processing', [
                'media_id' => $this->mediaId,
                'path' => $absolutePath,
            ]);

            return;
        }

        try {
            // Generate responsive variants
            $variants = $imageProcessingService->generateVariants($absolutePath);

            // Convert to WebP
            $webp = $imageProcessingService->convertToWebP($absolutePath);

            // Update metadata
            $metadata = array_merge($media->metadata ?? [], [
                'variants' => $variants,
                'webp' => $webp,
                'processed_at' => now()->toIso8601String(),
            ]);

            $media->update(['metadata' => $metadata]);

            Log::info('Image processed successfully', [
                'media_id' => $this->mediaId,
                'variants_count' => count($variants),
                'webp_created' => ! empty($webp),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process image', [
                'media_id' => $this->mediaId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Image processing job failed permanently', [
            'media_id' => $this->mediaId,
            'error' => $exception->getMessage(),
        ]);
    }
}
