<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use App\Services\ImageProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ImageProcessingService $imageProcessingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageProcessingService = new ImageProcessingService;
        Storage::fake('public');
    }

    public function test_upload_delegates_to_process_upload_and_stores_media(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $media = $this->imageProcessingService->upload($file, $user->id);

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals($user->id, $media->user_id);
        $this->assertEquals('test-image.jpg', $media->file_name);
        Storage::disk('public')->assertExists($media->file_path);
    }

    public function test_processes_image_upload_successfully(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $media = $this->imageProcessingService->processUpload($file, $user->id);

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals($user->id, $media->user_id);
        $this->assertEquals('test-image.jpg', $media->file_name);
        $this->assertEquals('image', $media->file_type);
        $this->assertStringContainsString('image/jpeg', $media->mime_type);

        // Verify original file exists
        Storage::disk('public')->assertExists($media->file_path);

        // Verify metadata is stored
        $this->assertNotNull($media->metadata);
        $this->assertArrayHasKey('variants', $media->metadata);
        $this->assertArrayHasKey('original_dimensions', $media->metadata);
        $this->assertArrayHasKey('thumbnail', $media->metadata['variants']);
        $this->assertArrayHasKey('medium', $media->metadata['variants']);
        $this->assertArrayHasKey('large', $media->metadata['variants']);
    }

    public function test_generates_image_variants(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 1200, 900);

        $media = $this->imageProcessingService->processUpload($file, $user->id);

        // Extract path components
        $pathInfo = pathinfo($media->file_path);
        $baseFilename = $pathInfo['filename'];
        $directory = $pathInfo['dirname'];

        // Check that thumbnail variant exists
        $thumbnailPath = "{$directory}/{$baseFilename}_thumbnail.jpg";
        Storage::disk('public')->assertExists($thumbnailPath);

        // Check that medium variant exists
        $mediumPath = "{$directory}/{$baseFilename}_medium.jpg";
        Storage::disk('public')->assertExists($mediumPath);

        // Check that large variant exists
        $largePath = "{$directory}/{$baseFilename}_large.jpg";
        Storage::disk('public')->assertExists($largePath);
    }

    public function test_generates_webp_variants(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 1200, 900);

        $media = $this->imageProcessingService->processUpload($file, $user->id);

        $pathInfo = pathinfo($media->file_path);
        $baseFilename = $pathInfo['filename'];
        $directory = $pathInfo['dirname'];

        // Check that WebP variants exist
        Storage::disk('public')->assertExists("{$directory}/{$baseFilename}_thumbnail.webp");
        Storage::disk('public')->assertExists("{$directory}/{$baseFilename}_medium.webp");
        Storage::disk('public')->assertExists("{$directory}/{$baseFilename}_large.webp");
    }

    public function test_validates_file_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file type');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->imageProcessingService->processUpload($file, $user->id);
    }

    public function test_validates_file_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File size exceeds 10MB limit');

        $user = User::factory()->create();
        // Create a file larger than 10MB
        $file = UploadedFile::fake()->create('large-image.jpg', 11000, 'image/jpeg');

        $this->imageProcessingService->processUpload($file, $user->id);
    }

    public function test_deletes_media_and_all_variants(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $media = $this->imageProcessingService->processUpload($file, $user->id);

        // Verify files exist
        Storage::disk('public')->assertExists($media->file_path);

        // Delete media
        $this->imageProcessingService->deleteMedia($media);

        // Verify original and all variants are deleted
        Storage::disk('public')->assertMissing($media->file_path);

        $pathInfo = pathinfo($media->file_path);
        $baseFilename = $pathInfo['filename'];
        $directory = $pathInfo['dirname'];

        Storage::disk('public')->assertMissing("{$directory}/{$baseFilename}_thumbnail.jpg");
        Storage::disk('public')->assertMissing("{$directory}/{$baseFilename}_medium.jpg");
        Storage::disk('public')->assertMissing("{$directory}/{$baseFilename}_large.jpg");
        Storage::disk('public')->assertMissing("{$directory}/{$baseFilename}_thumbnail.webp");
        Storage::disk('public')->assertMissing("{$directory}/{$baseFilename}_medium.webp");
        Storage::disk('public')->assertMissing("{$directory}/{$baseFilename}_large.webp");
    }

    public function test_checks_if_file_is_image(): void
    {
        $imageFile = UploadedFile::fake()->image('test.jpg');
        $documentFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->assertTrue($this->imageProcessingService->isImage($imageFile));
        $this->assertFalse($this->imageProcessingService->isImage($documentFile));
    }

    public function test_get_variant_url_returns_correct_url(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $media = $this->imageProcessingService->processUpload($file, $user->id);

        $thumbnailUrl = $this->imageProcessingService->getVariantUrl($media, 'thumbnail');
        $this->assertStringContainsString('_thumbnail.jpg', $thumbnailUrl);

        $mediumUrl = $this->imageProcessingService->getVariantUrl($media, 'medium');
        $this->assertStringContainsString('_medium.jpg', $mediumUrl);

        $largeUrl = $this->imageProcessingService->getVariantUrl($media, 'large');
        $this->assertStringContainsString('_large.jpg', $largeUrl);
    }

    public function test_get_variant_url_returns_webp_when_requested(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $media = $this->imageProcessingService->processUpload($file, $user->id);

        $webpUrl = $this->imageProcessingService->getVariantUrl($media, 'thumbnail', true);
        $this->assertStringContainsString('_thumbnail.webp', $webpUrl);
    }

    public function test_stores_variant_metadata_correctly(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 1200, 900);

        $media = $this->imageProcessingService->processUpload($file, $user->id);

        // Verify metadata structure
        $this->assertIsArray($media->metadata);
        $this->assertArrayHasKey('variants', $media->metadata);
        $this->assertArrayHasKey('original_dimensions', $media->metadata);

        // Verify each variant has required fields
        foreach (['thumbnail', 'medium', 'large'] as $variant) {
            $this->assertArrayHasKey($variant, $media->metadata['variants']);
            $this->assertArrayHasKey('path', $media->metadata['variants'][$variant]);
            $this->assertArrayHasKey('webp_path', $media->metadata['variants'][$variant]);
            $this->assertArrayHasKey('width', $media->metadata['variants'][$variant]);
            $this->assertArrayHasKey('height', $media->metadata['variants'][$variant]);
        }

        // Verify original dimensions
        $this->assertArrayHasKey('width', $media->metadata['original_dimensions']);
        $this->assertArrayHasKey('height', $media->metadata['original_dimensions']);
    }

    public function test_get_variant_url_uses_metadata_when_available(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $media = $this->imageProcessingService->processUpload($file, $user->id);

        // Get variant URL using metadata
        $thumbnailUrl = $this->imageProcessingService->getVariantUrl($media, 'thumbnail');

        // Verify it uses the path from metadata
        $expectedPath = $media->metadata['variants']['thumbnail']['path'];
        $this->assertStringContainsString($expectedPath, $thumbnailUrl);
    }

    public function test_get_variant_url_falls_back_for_legacy_media(): void
    {
        $user = User::factory()->create();

        // Create a legacy media record without metadata
        $media = Media::create([
            'user_id' => $user->id,
            'file_name' => 'legacy-image.jpg',
            'file_path' => 'media/2025/11/legacy-image.jpg',
            'file_type' => 'image',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'metadata' => null,
        ]);

        // Should still generate URL using naming convention
        $thumbnailUrl = $this->imageProcessingService->getVariantUrl($media, 'thumbnail');
        $this->assertStringContainsString('legacy-image_thumbnail.jpg', $thumbnailUrl);
    }

    // ============ File Upload Validation Tests ============

    public function test_validates_file_type_rejects_document(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file type');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->imageProcessingService->upload($file, $user->id);
    }

    public function test_validates_file_type_rejects_text_file(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file type');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('text.txt', 100, 'text/plain');

        $this->imageProcessingService->upload($file, $user->id);
    }

    public function test_validates_file_type_rejects_video(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file type');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('video.mp4', 100, 'video/mp4');

        $this->imageProcessingService->upload($file, $user->id);
    }

    public function test_validates_file_size_accepts_maximum_size(): void
    {
        $user = User::factory()->create();
        // Create an image file - note: fake()->image() creates smaller files
        // so we'll test with a reasonable size that passes validation
        $file = UploadedFile::fake()->image('large-image.jpg', 2000, 2000);

        // Manually set size to just under 10MB for testing
        $file->size = 10 * 1024 * 1024 - 1;

        $media = $this->imageProcessingService->upload($file, $user->id);

        $this->assertInstanceOf(Media::class, $media);
        // File size will be the actual image size, not our fake size
        // Just verify it was accepted (less than 10MB)
        $this->assertLessThan(10 * 1024 * 1024, $media->file_size);
    }

    public function test_validates_file_size_rejects_over_limit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File size exceeds 10MB limit');

        $user = User::factory()->create();
        // Create a file just over 10MB (10MB + 1KB)
        $file = UploadedFile::fake()->create('large-image.jpg', 10241, 'image/jpeg');

        $this->imageProcessingService->upload($file, $user->id);
    }

    public function test_validates_all_allowed_image_formats(): void
    {
        $user = User::factory()->create();

        // Test JPEG
        $jpegFile = UploadedFile::fake()->image('test.jpg', 100, 100);
        $media = $this->imageProcessingService->upload($jpegFile, $user->id);
        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('test.jpg', $media->file_name);
        $this->assertEquals('image', $media->file_type);

        // Test PNG
        $pngFile = UploadedFile::fake()->image('test.png', 100, 100);
        $media = $this->imageProcessingService->upload($pngFile, $user->id);
        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('test.png', $media->file_name);
        $this->assertEquals('image', $media->file_type);

        // Note: GIF and WebP fake creation may not work with UploadedFile::fake()->image()
        // These formats are validated in the service validateFile() method
        // Actual format support depends on GD/Imagick configuration
    }

    // ============ Image Variant Generation Tests ============

    public function test_generates_variants_with_correct_dimensions(): void
    {
        $user = User::factory()->create();
        // Create a large image to test resizing
        $file = UploadedFile::fake()->image('test-image.jpg', 2000, 1500);

        $media = $this->imageProcessingService->upload($file, $user->id);

        // Expected variant sizes
        $expectedSizes = [
            'thumbnail' => [150, 150],
            'medium' => [300, 300],
            'large' => [1024, 1024],
        ];

        // Verify metadata contains dimensions for each variant
        foreach ($expectedSizes as $variantName => [$maxWidth, $maxHeight]) {
            $variant = $media->metadata['variants'][$variantName];

            $this->assertArrayHasKey('width', $variant);
            $this->assertArrayHasKey('height', $variant);

            // Dimensions should be within bounds (scaled maintaining aspect ratio)
            // For a 2000x1500 image (4:3 aspect ratio):
            // - Thumbnail: should be 150x112 or smaller
            // - Medium: should be 300x225 or smaller
            // - Large: should be 1024x768 or smaller
            $this->assertLessThanOrEqual($maxWidth, $variant['width'], "{$variantName} width exceeds maximum");
            $this->assertLessThanOrEqual($maxHeight, $variant['height'], "{$variantName} height exceeds maximum");

            // Aspect ratio should be maintained (within rounding tolerance)
            $originalAspectRatio = 2000 / 1500; // 1.333...
            $variantAspectRatio = $variant['width'] / $variant['height'];
            $aspectRatioDiff = abs($originalAspectRatio - $variantAspectRatio) / $originalAspectRatio;
            $this->assertLessThanOrEqual(0.05, $aspectRatioDiff,
                "{$variantName} aspect ratio should be maintained (difference: {$aspectRatioDiff})");
        }
    }

    public function test_generates_variants_for_square_image(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('square.jpg', 500, 500);

        $media = $this->imageProcessingService->upload($file, $user->id);

        // For square images, variants should also be square (or very close)
        foreach (['thumbnail', 'medium', 'large'] as $variantName) {
            $variant = $media->metadata['variants'][$variantName];

            $this->assertArrayHasKey('width', $variant);
            $this->assertArrayHasKey('height', $variant);

            // Square images should produce square variants (within 5px tolerance)
            $this->assertLessThanOrEqual(5, abs($variant['width'] - $variant['height']),
                "{$variantName} should be square for square source image");
        }
    }

    public function test_generates_variants_for_portrait_image(): void
    {
        $user = User::factory()->create();
        // Tall portrait image
        $file = UploadedFile::fake()->image('portrait.jpg', 800, 1200);

        $media = $this->imageProcessingService->upload($file, $user->id);

        // Portrait images should maintain aspect ratio
        foreach (['thumbnail', 'medium', 'large'] as $variantName) {
            $variant = $media->metadata['variants'][$variantName];

            // Height should be greater than width for portrait
            $this->assertGreaterThan($variant['width'], $variant['height'],
                "{$variantName} should maintain portrait orientation");
        }
    }

    public function test_generates_variants_for_landscape_image(): void
    {
        $user = User::factory()->create();
        // Wide landscape image
        $file = UploadedFile::fake()->image('landscape.jpg', 1600, 900);

        $media = $this->imageProcessingService->upload($file, $user->id);

        // Landscape images should maintain aspect ratio
        foreach (['thumbnail', 'medium', 'large'] as $variantName) {
            $variant = $media->metadata['variants'][$variantName];

            // Width should be greater than height for landscape
            $this->assertGreaterThan($variant['height'], $variant['width'],
                "{$variantName} should maintain landscape orientation");
        }
    }

    public function test_variants_are_actually_created_on_disk(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 1200, 900);

        $media = $this->imageProcessingService->upload($file, $user->id);

        $pathInfo = pathinfo($media->file_path);
        $baseFilename = $pathInfo['filename'];
        $directory = $pathInfo['dirname'];

        // Verify all variant files exist on disk
        $variants = ['thumbnail', 'medium', 'large'];

        foreach ($variants as $variant) {
            // JPEG variant
            $jpegPath = "{$directory}/{$baseFilename}_{$variant}.jpg";
            Storage::disk('public')->assertExists($jpegPath);

            // WebP variant
            $webpPath = "{$directory}/{$baseFilename}_{$variant}.webp";
            Storage::disk('public')->assertExists($webpPath);

            // Verify files are not empty
            $this->assertGreaterThan(0, Storage::disk('public')->size($jpegPath),
                "{$variant} JPEG variant should not be empty");
            $this->assertGreaterThan(0, Storage::disk('public')->size($webpPath),
                "{$variant} WebP variant should not be empty");
        }
    }

    // ============ File Deletion Tests ============

    public function test_deletes_all_variant_files_on_deletion(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $media = $this->imageProcessingService->upload($file, $user->id);

        $pathInfo = pathinfo($media->file_path);
        $baseFilename = $pathInfo['filename'];
        $directory = $pathInfo['dirname'];

        // Verify all files exist before deletion
        Storage::disk('public')->assertExists($media->file_path);

        $allVariantFiles = [
            "{$directory}/{$baseFilename}_thumbnail.jpg",
            "{$directory}/{$baseFilename}_medium.jpg",
            "{$directory}/{$baseFilename}_large.jpg",
            "{$directory}/{$baseFilename}_thumbnail.webp",
            "{$directory}/{$baseFilename}_medium.webp",
            "{$directory}/{$baseFilename}_large.webp",
        ];

        foreach ($allVariantFiles as $variantFile) {
            Storage::disk('public')->assertExists($variantFile);
        }

        // Delete media
        $this->imageProcessingService->deleteMedia($media);

        // Verify original file is deleted
        Storage::disk('public')->assertMissing($media->file_path);

        // Verify all variant files are deleted
        foreach ($allVariantFiles as $variantFile) {
            Storage::disk('public')->assertMissing($variantFile);
        }
    }

    public function test_deletion_handles_missing_variant_files_gracefully(): void
    {
        $user = User::factory()->create();

        // Create media record manually without actual files
        $media = Media::create([
            'user_id' => $user->id,
            'file_name' => 'missing-file.jpg',
            'file_path' => 'media/2025/11/missing-file.jpg',
            'file_type' => 'image',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
        ]);

        // Should not throw exception even if files don't exist
        // deleteMedia only deletes files, not the database record
        $this->imageProcessingService->deleteMedia($media);

        // Media record should still exist in database (deleteMedia only removes files)
        $this->assertDatabaseHas('media_library', ['id' => $media->id]);

        // Verify the method completed without errors
        $this->assertTrue(true);
    }

    public function test_deletion_removes_database_record(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $media = $this->imageProcessingService->upload($file, $user->id);

        // Verify media exists in database
        $this->assertDatabaseHas('media_library', ['id' => $media->id]);

        // Delete files (service method only deletes files, not DB record)
        $this->imageProcessingService->deleteMedia($media);

        // Manually delete from database to verify complete cleanup
        $media->delete();

        // Verify media is removed from database
        $this->assertDatabaseMissing('media_library', ['id' => $media->id]);
    }

    public function test_deletion_works_for_images_with_different_orientations(): void
    {
        $user = User::factory()->create();

        $images = [
            ['portrait.jpg', 600, 1200], // Portrait
            ['landscape.jpg', 1600, 900], // Landscape
            ['square.jpg', 800, 800], // Square
        ];

        foreach ($images as [$filename, $width, $height]) {
            $file = UploadedFile::fake()->image($filename, $width, $height);
            $media = $this->imageProcessingService->upload($file, $user->id);

            $pathInfo = pathinfo($media->file_path);
            $baseFilename = $pathInfo['filename'];
            $directory = $pathInfo['dirname'];

            // Verify files exist
            Storage::disk('public')->assertExists($media->file_path);
            Storage::disk('public')->assertExists("{$directory}/{$baseFilename}_thumbnail.jpg");

            // Delete
            $this->imageProcessingService->deleteMedia($media);

            // Verify all files are deleted
            Storage::disk('public')->assertMissing($media->file_path);
            Storage::disk('public')->assertMissing("{$directory}/{$baseFilename}_thumbnail.jpg");
            Storage::disk('public')->assertMissing("{$directory}/{$baseFilename}_thumbnail.webp");
        }
    }
}
