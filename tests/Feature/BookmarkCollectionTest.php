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

    public function test_user_can_move_bookmark_between_collections(): void
    {
        $user = User::factory()->create();
        $collection1 = BookmarkCollection::factory()->create(['user_id' => $user->id, 'name' => 'Collection 1']);
        $collection2 = BookmarkCollection::factory()->create(['user_id' => $user->id, 'name' => 'Collection 2']);
        $post = Post::factory()->create(['status' => 'published']);
        
        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'collection_id' => $collection1->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('bookmarks.move', $bookmark), [
                'collection_id' => $collection2->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'collection_id' => $collection2->id,
        ]);
    }

    public function test_user_can_move_bookmark_to_no_collection(): void
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
            ->postJson(route('bookmarks.move', $bookmark), [
                'collection_id' => null,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'collection_id' => null,
        ]);
    }

    public function test_user_cannot_move_another_users_bookmark(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user2->id]);
        $post = Post::factory()->create(['status' => 'published']);
        
        $bookmark = Bookmark::create([
            'user_id' => $user1->id,
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($user2)
            ->postJson(route('bookmarks.move', $bookmark), [
                'collection_id' => $collection->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_move_bookmark_to_another_users_collection(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user2->id]);
        $post = Post::factory()->create(['status' => 'published']);
        
        $bookmark = Bookmark::create([
            'user_id' => $user1->id,
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($user1)
            ->postJson(route('bookmarks.move', $bookmark), [
                'collection_id' => $collection->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_collection_displays_bookmarks_with_filtering(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user->id]);
        
        $category1 = \App\Models\Category::factory()->create(['name' => 'PHP']);
        $category2 = \App\Models\Category::factory()->create(['name' => 'JavaScript']);
        
        $post1 = Post::factory()->create(['status' => 'published', 'category_id' => $category1->id]);
        $post2 = Post::factory()->create(['status' => 'published', 'category_id' => $category2->id]);
        $post3 = Post::factory()->create(['status' => 'published', 'category_id' => $category1->id]);

        Bookmark::create(['user_id' => $user->id, 'post_id' => $post1->id, 'collection_id' => $collection->id]);
        Bookmark::create(['user_id' => $user->id, 'post_id' => $post2->id, 'collection_id' => $collection->id]);
        Bookmark::create(['user_id' => $user->id, 'post_id' => $post3->id, 'collection_id' => $collection->id]);

        $response = $this->actingAs($user)
            ->get(route('bookmarks.collection', $collection));

        $response->assertStatus(200)
            ->assertViewHas('bookmarks')
            ->assertSee($post1->title)
            ->assertSee($post2->title)
            ->assertSee($post3->title);
    }

    public function test_collection_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('bookmarks.collections.store'), [
                'name' => '',
                'description' => 'Test description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_collection_name_has_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('bookmarks.collections.store'), [
                'name' => str_repeat('a', 256),
                'description' => 'Test description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_collection_description_has_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('bookmarks.collections.store'), [
                'name' => 'Test Collection',
                'description' => str_repeat('a', 1001),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_user_can_toggle_collection_visibility(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->private()->create(['user_id' => $user->id]);

        $this->assertFalse($collection->is_public);

        $response = $this->actingAs($user)
            ->putJson(route('bookmarks.collections.update', $collection), [
                'name' => $collection->name,
                'is_public' => true,
            ]);

        $response->assertStatus(200);

        $this->assertTrue($collection->fresh()->is_public);
    }

    public function test_collections_are_ordered_correctly(): void
    {
        $user = User::factory()->create();
        
        $collection1 = BookmarkCollection::factory()->create(['user_id' => $user->id, 'order' => 0]);
        $collection2 = BookmarkCollection::factory()->create(['user_id' => $user->id, 'order' => 1]);
        $collection3 = BookmarkCollection::factory()->create(['user_id' => $user->id, 'order' => 2]);

        $collections = $user->bookmarkCollections;

        $this->assertEquals($collection1->id, $collections[0]->id);
        $this->assertEquals($collection2->id, $collections[1]->id);
        $this->assertEquals($collection3->id, $collections[2]->id);
    }

    public function test_deleting_collection_preserves_bookmarks(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user->id]);
        $posts = Post::factory()->count(3)->create(['status' => 'published']);

        $bookmarkIds = [];
        foreach ($posts as $post) {
            $bookmark = Bookmark::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'collection_id' => $collection->id,
            ]);
            $bookmarkIds[] = $bookmark->id;
        }

        $this->actingAs($user)
            ->deleteJson(route('bookmarks.collections.destroy', $collection));

        // All bookmarks should still exist
        foreach ($bookmarkIds as $bookmarkId) {
            $this->assertDatabaseHas('bookmarks', [
                'id' => $bookmarkId,
                'collection_id' => null,
            ]);
        }
    }

    public function test_empty_collection_can_be_viewed(): void
    {
        $user = User::factory()->create();
        $collection = BookmarkCollection::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('bookmarks.collection', $collection));

        $response->assertStatus(200)
            ->assertViewIs('bookmarks.collection')
            ->assertSee('No bookmarks in this collection');
    }
}
