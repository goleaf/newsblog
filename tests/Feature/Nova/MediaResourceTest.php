<?php

namespace Tests\Feature\Nova;

use App\Models\Media;
use App\Models\User;
use App\Nova\Media as MediaResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class MediaResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_resource_has_correct_model(): void
    {
        $this->assertEquals(\App\Models\Media::class, MediaResource::$model);
    }

    public function test_media_resource_has_correct_title(): void
    {
        $this->assertEquals('file_name', MediaResource::$title);
    }

    public function test_media_resource_has_correct_search_fields(): void
    {
        $expected = ['id', 'file_name', 'title', 'alt_text'];
        $this->assertEquals($expected, MediaResource::$search);
    }

    public function test_admin_can_view_any_media(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/media', 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(MediaResource::authorizedToViewAny($request));
    }

    public function test_editor_can_view_any_media(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $request = NovaRequest::create('/nova-api/media', 'GET');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue(MediaResource::authorizedToViewAny($request));
    }

    public function test_author_can_view_any_media(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $request = NovaRequest::create('/nova-api/media', 'GET');
        $request->setUserResolver(fn () => $author);

        $this->assertTrue(MediaResource::authorizedToViewAny($request));
    }

    public function test_admin_can_view_any_media_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'author']);
        $media = Media::factory()->create(['user_id' => $user->id]);

        $resource = new MediaResource($media);
        $request = NovaRequest::create('/nova-api/media/'.$media->id, 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToView($request));
    }

    public function test_editor_can_view_any_media_file(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $user = User::factory()->create(['role' => 'author']);
        $media = Media::factory()->create(['user_id' => $user->id]);

        $resource = new MediaResource($media);
        $request = NovaRequest::create('/nova-api/media/'.$media->id, 'GET');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue($resource->authorizedToView($request));
    }

    public function test_author_can_view_own_media(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $media = Media::factory()->create(['user_id' => $author->id]);

        $resource = new MediaResource($media);
        $request = NovaRequest::create('/nova-api/media/'.$media->id, 'GET');
        $request->setUserResolver(fn () => $author);

        $this->assertTrue($resource->authorizedToView($request));
    }

    public function test_author_cannot_view_others_media(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $otherUser = User::factory()->create(['role' => 'author']);
        $media = Media::factory()->create(['user_id' => $otherUser->id]);

        $resource = new MediaResource($media);
        $request = NovaRequest::create('/nova-api/media/'.$media->id, 'GET');
        $request->setUserResolver(fn () => $author);

        $this->assertFalse($resource->authorizedToView($request));
    }

    public function test_admin_can_create_media(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/media', 'POST');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(MediaResource::authorizedToCreate($request));
    }

    public function test_author_can_create_media(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $request = NovaRequest::create('/nova-api/media', 'POST');
        $request->setUserResolver(fn () => $author);

        $this->assertTrue(MediaResource::authorizedToCreate($request));
    }

    public function test_admin_can_update_any_media(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'author']);
        $media = Media::factory()->create(['user_id' => $user->id]);

        $resource = new MediaResource($media);
        $request = NovaRequest::create('/nova-api/media/'.$media->id, 'PUT');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToUpdate($request));
    }

    public function test_author_can_update_own_media(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $media = Media::factory()->create(['user_id' => $author->id]);

        $resource = new MediaResource($media);
        $request = NovaRequest::create('/nova-api/media/'.$media->id, 'PUT');
        $request->setUserResolver(fn () => $author);

        $this->assertTrue($resource->authorizedToUpdate($request));
    }

    public function test_author_cannot_update_others_media(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $otherUser = User::factory()->create(['role' => 'author']);
        $media = Media::factory()->create(['user_id' => $otherUser->id]);

        $resource = new MediaResource($media);
        $request = NovaRequest::create('/nova-api/media/'.$media->id, 'PUT');
        $request->setUserResolver(fn () => $author);

        $this->assertFalse($resource->authorizedToUpdate($request));
    }

    public function test_admin_can_delete_any_media(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'author']);
        $media = Media::factory()->create(['user_id' => $user->id]);

        $resource = new MediaResource($media);
        $request = NovaRequest::create('/nova-api/media/'.$media->id, 'DELETE');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToDelete($request));
    }

    public function test_author_can_delete_own_media(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $media = Media::factory()->create(['user_id' => $author->id]);

        $resource = new MediaResource($media);
        $request = NovaRequest::create('/nova-api/media/'.$media->id, 'DELETE');
        $request->setUserResolver(fn () => $author);

        $this->assertTrue($resource->authorizedToDelete($request));
    }

    public function test_author_cannot_delete_others_media(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $otherUser = User::factory()->create(['role' => 'author']);
        $media = Media::factory()->create(['user_id' => $otherUser->id]);

        $resource = new MediaResource($media);
        $request = NovaRequest::create('/nova-api/media/'.$media->id, 'DELETE');
        $request->setUserResolver(fn () => $author);

        $this->assertFalse($resource->authorizedToDelete($request));
    }

    public function test_media_resource_has_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $media = Media::factory()->create();
        $resource = new MediaResource($media);

        $request = NovaRequest::create('/nova-api/media', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);

        $this->assertNotEmpty($fields);
        $this->assertGreaterThan(10, count($fields));
    }

    public function test_media_index_query_eager_loads_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Media::factory()->count(3)->create();

        $request = NovaRequest::create('/nova-api/media', 'GET');
        $request->setUserResolver(fn () => $admin);

        $query = MediaResource::indexQuery($request, Media::query());
        $media = $query->get();

        $this->assertCount(3, $media);
        $this->assertTrue($media->first()->relationLoaded('user'));
    }
}
