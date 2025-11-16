<?php

namespace Tests\Feature\Api;

use App\Models\Bookmark;
use App\Models\BookmarkCollection;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingListApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_and_items_flow(): void
    {
        $user = User::factory()->create();

        // Create list
        $create = $this->actingAs($user, 'sanctum')->postJson('/api/v1/reading-lists', [
            'name' => 'My List',
            'description' => 'Desc',
            'is_public' => true,
        ]);
        $create->assertCreated();
        $listId = $create->json('data.id') ?? $create->json('id');

        // Show list
        $show = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reading-lists/'.$listId);
        $show->assertOk();

        // Add item
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);
        $add = $this->actingAs($user, 'sanctum')->postJson('/api/v1/reading-lists/'.$listId.'/items', [
            'post_id' => $post->id,
            'note' => 'Read later',
        ]);
        $add->assertCreated();
        $bookmarkId = $add->json('data.id') ?? $add->json('id');

        // Reorder items
        $secondPost = Post::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/reading-lists/'.$listId.'/items', [
            'post_id' => $secondPost->id,
        ])->assertCreated();
        $bookmarkIds = Bookmark::where('user_id', $user->id)->where('collection_id', $listId)->pluck('id')->toArray();
        $reorder = $this->actingAs($user, 'sanctum')->postJson('/api/v1/reading-lists/'.$listId.'/reorder', [
            'bookmark_ids' => array_reverse($bookmarkIds),
        ]);
        $reorder->assertOk();

        // Remove item
        $remove = $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/reading-lists/'.$listId.'/items/'.$bookmarkId);
        $remove->assertNoContent();

        // Update list
        $update = $this->actingAs($user, 'sanctum')->putJson('/api/v1/reading-lists/'.$listId, [
            'name' => 'My List 2',
            'description' => 'Updated',
            'is_public' => false,
        ]);
        $update->assertOk();

        // Delete list
        $del = $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/reading-lists/'.$listId);
        $del->assertNoContent();
        $this->assertDatabaseMissing('bookmark_collections', ['id' => $listId]);
    }

    public function test_public_access_to_public_list(): void
    {
        $owner = User::factory()->create();
        $list = BookmarkCollection::factory()->create(['user_id' => $owner->id, 'is_public' => true]);
        $guest = User::factory()->create();

        $this->actingAs($guest, 'sanctum')->getJson('/api/v1/reading-lists/'.$list->id)
            ->assertOk();
    }
}
