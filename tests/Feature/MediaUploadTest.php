<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_validation_fails_for_non_image(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_successful_image_upload_generates_variants(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('photo.jpg', 1200, 900);

        $response = $this->postJson('/api/v1/media', ['file' => $file]);
        $response->assertCreated();

        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('path', $data);
        $this->assertArrayHasKey('metadata', $data);

        $media = Media::query()->findOrFail((int) $data['id']);
        $this->assertNotEmpty($media->metadata['variants'] ?? []);

        // Assert original stored
        Storage::disk('public')->assertExists(str_replace('public/', '', $media->path));

        // Assert variants stored
        foreach ($media->metadata['variants'] as $variantPath) {
            Storage::disk('public')->assertExists(str_replace('public/', '', $variantPath));
        }
    }

    public function test_delete_media_removes_files(): void
    {
        Storage::fake('public');

        // Upload first
        $file = UploadedFile::fake()->image('photo.jpg', 1000, 800);
        $response = $this->postJson('/api/v1/media', ['file' => $file]);
        $response->assertCreated();
        $id = (int) $response->json('id');

        $media = Media::query()->findOrFail($id);

        // Check original exist
        Storage::disk('public')->assertExists(str_replace('public/', '', $media->path));

        // Delete
        $del = $this->deleteJson("/api/v1/media/{$id}");
        $del->assertOk()->assertJson(['deleted' => true]);

        // Assert files deleted
        Storage::disk('public')->assertMissing(str_replace('public/', '', $media->path));
        foreach ((array) ($media->metadata['variants'] ?? []) as $variantPath) {
            Storage::disk('public')->assertMissing(str_replace('public/', '', $variantPath));
        }
    }
}
