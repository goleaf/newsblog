<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\BookmarkCollection;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_bookmark_collection(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('bookmarks.collections.store'), [
                'name' => 'Must Read',
                'description' => 'Articles I must read',
                'is_public' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('bookmark_collections', [
            'user_id' => $user->id,
            'name' => 'Must Read',
            'description' => 'Articles I must read',
            'is_public' => false,
        ]);
    }

    public function test_user_can_update_bookmark_collection(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($user)
            ->putJson(route('bookmarks.collections.update', $collection), [
                'name' => 'New Name',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('bookmark_collections', [
            'id' => $collection->id,
            'name' => 'New Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_user_cannot_update_another_users_collection(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->putJson(route('bookmarks.collections.update', $collection), [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_bookmark_collection(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->create(['status' => 'published']);
        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(route('bookmarks.collections.destroy', $collection));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('bookmark_collections', [
            'id' => $collection->id,
        ]);

        // Bookmark should still exist but with null collection_id
        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'collection_id' => null,
        ]);
    }

    public function test_user_can_view_collection(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user->id]);
        $posts = Post::factory()->count(3)->create(['status' => 'published']);

        foreach ($posts as $post) {
            Bookmark::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'collection_id' => $collection->id,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('bookmarks.collection', $collection));

        $response->assertStatus(200)
            ->assertViewIs('bookmarks.collection')
            ->assertViewHas('collection')
            ->assertViewHas('bookmarks');
    }

    public function test_user_can_view_public_collection_from_another_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $collection = BookmarkCollection::factory()->public()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->get(route('bookmarks.collection', $collection));

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_private_collection_from_another_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $collection = BookmarkCollection::factory()->private()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->get(route('bookmarks.collection', $collection));

        $response->assertStatus(403);
    }

    public function test_bookmarks_can_be_reordered_in_collection(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user->id]);
        $posts = Post::factory()->count(3)->create(['status' => 'published']);

        $bookmarks = [];
        foreach ($posts as $post) {
            $bookmarks[] = Bookmark::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'collection_id' => $collection->id,
            ]);
        }

        $reorderedIds = [$bookmarks[2]->id, $bookmarks[0]->id, $bookmarks[1]->id];

        $response = $this->actingAs($user)
            ->postJson(route('bookmarks.collections.reorder', $collection), [
                'bookmark_ids' => $reorderedIds,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertEquals(0, Bookmark::find($reorderedIds[0])->order);
        $this->assertEquals(1, Bookmark::find($reorderedIds[1])->order);
        $this->assertEquals(2, Bookmark::find($reorderedIds[2])->order);
    }

    public function test_collections_are_displayed_on_bookmarks_page(): void
    {
        $user = User::factory()->create();
        $collections = BookmarkCollection::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('bookmarks.index'));

        $response->assertStatus(200)
            ->assertViewHas('collections');

        $viewCollections = $response->viewData('collections');
        $this->assertCount(3, $viewCollections);
    }

    public function test_guest_cannot_create_collection(): void
    {
        $response = $this->postJson(route('bookmarks.collections.store'), [
            'name' => 'Test Collection',
        ]);

        $response->assertStatus(401);
    }
}
