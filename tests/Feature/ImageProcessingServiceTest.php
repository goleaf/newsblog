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
}
