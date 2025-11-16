<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_admin_can_upload_media(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.media.store'), [
                'file' => $file,
                'user_id' => $admin->id,
                'alt_text' => 'Alt text',
                'caption' => 'Caption text',
            ]);

        $response->assertCreated();
        $response->assertJsonFragment(['message' => 'Media uploaded successfully.']);

        $this->assertDatabaseCount('media_library', 1);

        $media = Media::first();

        Storage::disk('public')->assertExists($media->file_path);
    }

    public function test_index_returns_paginated_media_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();

        Media::factory()->count(3)->create([
            'user_id' => $owner->id,
            'file_type' => 'image',
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.media.index'));

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function test_destroy_deletes_media_and_files(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();

        $media = Media::factory()->create([
            'user_id' => $owner->id,
            'file_type' => 'image',
            'file_path' => 'media/test-image.jpg',
        ]);

        Storage::disk('public')->put($media->file_path, 'dummy');

        $response = $this->actingAs($admin)
            ->deleteJson(route('admin.media.destroy', $media), ['id' => $media->id]);

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'Media deleted successfully.']);

        $this->assertDatabaseMissing('media_library', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($media->file_path);
    }

    public function test_search_returns_matching_media(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();

        Media::factory()->create([
            'user_id' => $owner->id,
            'file_type' => 'image',
            'file_name' => 'unique-file-name.jpg',
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.media.search', ['q' => 'unique-file-name']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
