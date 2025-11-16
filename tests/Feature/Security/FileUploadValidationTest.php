<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_upload_rejects_non_image_and_large_files(): void
    {
        Storage::fake('public');

        // Non-image file
        $response = $this->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->create('evil.php', 10, 'text/plain'),
        ]);
        $response->assertStatus(422);

        // Oversized image (11MB)
        $response = $this->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image('big.jpg')->size(11000),
        ]);
        $response->assertStatus(422);
    }
}


