<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_bookmark_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->actingAs($user)
            ->postJson("/posts/{$post->id}/bookmark");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'bookmarked' => true,
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_authenticated_user_can_unbookmark_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['status' => 'published']);

        Bookmark::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/posts/{$post->id}/bookmark");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'bookmarked' => false,
            ]);

        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_guest_cannot_bookmark_post(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->postJson("/posts/{$post->id}/bookmark");

        $response->assertStatus(401);
    }

    public function test_user_can_view_reading_list(): void
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(3)->create(['status' => 'published']);

        foreach ($posts as $post) {
            Bookmark::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('bookmarks.index'));

        $response->assertStatus(200)
            ->assertViewIs('bookmarks.index')
            ->assertViewHas('bookmarks');
    }

    public function test_reading_list_shows_only_user_bookmarks(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post1 = Post::factory()->create(['status' => 'published']);
        $post2 = Post::factory()->create(['status' => 'published']);

        Bookmark::create(['user_id' => $user1->id, 'post_id' => $post1->id]);
        Bookmark::create(['user_id' => $user2->id, 'post_id' => $post2->id]);

        $response = $this->actingAs($user1)
            ->get(route('bookmarks.index'));

        $response->assertStatus(200);
        $bookmarks = $response->viewData('bookmarks');

        $this->assertCount(1, $bookmarks);
        $this->assertEquals($post1->id, $bookmarks->first()->post_id);
    }

    public function test_post_has_is_bookmarked_by_method(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['status' => 'published']);

        $this->assertFalse($post->isBookmarkedBy($user->id));

        Bookmark::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $this->assertTrue($post->fresh()->isBookmarkedBy($user->id));
    }

    public function test_post_has_bookmarks_count_attribute(): void
    {
        $post = Post::factory()->create(['status' => 'published']);
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            Bookmark::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
        }

        $this->assertEquals(3, $post->fresh()->bookmarks_count);
    }

    public function test_bookmark_button_shows_correct_state(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->actingAs($user)
            ->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('data-bookmarked="false"', false);

        Bookmark::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('data-bookmarked="true"', false);
    }
}
