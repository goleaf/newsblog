<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\BookmarkCollection;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingListManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_reading_lists_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reading-lists.index'));

        $response->assertOk();
        $response->assertViewIs('reading-lists.index');
    }

    public function test_guest_cannot_view_reading_lists_index(): void
    {
        $response = $this->get(route('reading-lists.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_create_reading_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-lists.store'), [
            'name' => 'My Favorite Articles',
            'description' => 'A collection of my favorite tech articles',
            'is_public' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookmark_collections', [
            'user_id' => $user->id,
            'name' => 'My Favorite Articles',
            'description' => 'A collection of my favorite tech articles',
            'is_public' => false,
        ]);
    }

    public function test_reading_list_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-lists.store'), [
            'description' => 'A collection without a name',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_can_update_reading_list(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($user)->put(route('reading-lists.update', $collection), [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'is_public' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookmark_collections', [
            'id' => $collection->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'is_public' => true,
        ]);
    }

    public function test_user_cannot_update_another_users_reading_list(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->put(route('reading-lists.update', $collection), [
            'name' => 'Hacked Name',
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_delete_reading_list(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('reading-lists.destroy', $collection));

        $response->assertRedirect(route('reading-lists.index'));
        $this->assertDatabaseMissing('bookmark_collections', [
            'id' => $collection->id,
        ]);
    }

    public function test_deleting_reading_list_removes_collection_reference_from_bookmarks(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->create(['status' => 'published']);

        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'collection_id' => $collection->id,
        ]);

        $this->actingAs($user)->delete(route('reading-lists.destroy', $collection));

        $bookmark->refresh();
        $this->assertNull($bookmark->collection_id);
    }

    public function test_user_can_add_article_to_reading_list(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->actingAs($user)->post(route('reading-lists.add-item', $collection), [
            'post_id' => $post->id,
            'notes' => 'Great article!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'collection_id' => $collection->id,
            'notes' => 'Great article!',
        ]);
    }

    public function test_user_can_remove_article_from_reading_list(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->create(['status' => 'published']);

        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->actingAs($user)->delete(
            route('reading-lists.remove-item', [$collection, $bookmark])
        );

        $response->assertRedirect();
        $bookmark->refresh();
        $this->assertNull($bookmark->collection_id);
    }

    public function test_user_can_reorder_articles_in_reading_list(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user->id]);
        $posts = Post::factory()->count(3)->create(['status' => 'published']);

        $bookmarks = [];
        foreach ($posts as $index => $post) {
            $bookmarks[] = Bookmark::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'collection_id' => $collection->id,
                'order' => $index,
            ]);
        }

        // Reverse the order
        $newOrder = array_reverse(array_map(fn ($b) => $b->id, $bookmarks));

        $response = $this->actingAs($user)->post(
            route('reading-lists.reorder', $collection),
            ['bookmark_ids' => $newOrder]
        );

        $response->assertRedirect();

        foreach ($newOrder as $index => $bookmarkId) {
            $this->assertDatabaseHas('bookmarks', [
                'id' => $bookmarkId,
                'order' => $index,
            ]);
        }
    }

    public function test_user_can_generate_share_link(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create([
            'user_id' => $user->id,
            'share_token' => null,
        ]);

        $response = $this->actingAs($user)->post(route('reading-lists.share', $collection));

        $response->assertRedirect();
        $collection->refresh();
        $this->assertNotNull($collection->share_token);
        $this->assertTrue($collection->is_public);
    }

    public function test_user_can_revoke_share_link(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create([
            'user_id' => $user->id,
            'share_token' => 'test-token-123',
        ]);

        $response = $this->actingAs($user)->delete(route('reading-lists.revoke-share', $collection));

        $response->assertRedirect();
        $collection->refresh();
        $this->assertNull($collection->share_token);
    }

    public function test_anyone_can_view_shared_reading_list(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create([
            'user_id' => $user->id,
            'share_token' => 'public-token-123',
            'is_public' => true,
        ]);

        $response = $this->get(route('reading-lists.shared', 'public-token-123'));

        $response->assertOk();
        $response->assertViewIs('reading-lists.shared');
        $response->assertViewHas('collection', $collection);
    }

    public function test_viewing_shared_list_increments_view_count(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create([
            'user_id' => $user->id,
            'share_token' => 'public-token-123',
            'is_public' => true,
            'view_count' => 0,
        ]);

        $this->get(route('reading-lists.shared', 'public-token-123'));

        $collection->refresh();
        $this->assertEquals(1, $collection->view_count);
    }

    public function test_owner_viewing_shared_list_does_not_increment_view_count(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create([
            'user_id' => $user->id,
            'share_token' => 'public-token-123',
            'is_public' => true,
            'view_count' => 0,
        ]);

        $this->actingAs($user)->get(route('reading-lists.shared', 'public-token-123'));

        $collection->refresh();
        $this->assertEquals(0, $collection->view_count);
    }

    public function test_invalid_share_token_returns_404(): void
    {
        $response = $this->get(route('reading-lists.shared', 'invalid-token'));

        $response->assertNotFound();
    }

    public function test_user_can_view_own_private_reading_list(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create([
            'user_id' => $user->id,
            'is_public' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reading-lists.show', $collection));

        $response->assertOk();
    }

    public function test_user_cannot_view_another_users_private_reading_list(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $collection = BookmarkCollection::factory()->create([
            'user_id' => $user1->id,
            'is_public' => false,
        ]);

        $response = $this->actingAs($user2)->get(route('reading-lists.show', $collection));

        $response->assertForbidden();
    }

    public function test_user_can_view_another_users_public_reading_list(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $collection = BookmarkCollection::factory()->create([
            'user_id' => $user1->id,
            'is_public' => true,
        ]);

        $response = $this->actingAs($user2)->get(route('reading-lists.show', $collection));

        $response->assertOk();
    }
}
