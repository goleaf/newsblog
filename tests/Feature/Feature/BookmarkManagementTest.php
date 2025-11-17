<?php

namespace Tests\Feature\Feature;

use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function postJsonWithCookie(string $uri, array $data = [], string $cookieValue = ''): \Illuminate\Testing\TestResponse
    {
        return $this->call(
            'POST',
            $uri,
            $data,
            ['reader_token' => $cookieValue],
            [],
            $this->transformHeadersToServerVars(['Accept' => 'application/json', 'X-CSRF-TOKEN' => csrf_token()])
        );
    }

    protected function getWithCookie(string $uri, string $cookieValue = ''): \Illuminate\Testing\TestResponse
    {
        return $this->call(
            'GET',
            $uri,
            [],
            ['reader_token' => $cookieValue]
        );
    }

    public function test_user_can_mark_bookmark_as_read(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $readerToken = 'test-reader-token';
        $bookmark = Bookmark::create([
            'reader_token' => $readerToken,
            'post_id' => $post->id,
            'is_read' => false,
        ]);

        $response = $this->postJsonWithCookie("/bookmarks/{$bookmark->id}/read", [], $readerToken);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'is_read' => true,
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'is_read' => true,
        ]);

        $bookmark->refresh();
        $this->assertTrue($bookmark->is_read);
        $this->assertNotNull($bookmark->read_at);
    }

    public function test_user_can_mark_bookmark_as_unread(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $readerToken = 'test-reader-token';
        $bookmark = Bookmark::create([
            'reader_token' => $readerToken,
            'post_id' => $post->id,
            'is_read' => true,
            'read_at' => now(),
        ]);

        $response = $this->postJsonWithCookie("/bookmarks/{$bookmark->id}/unread", [], $readerToken);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'is_read' => false,
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'is_read' => false,
        ]);

        $bookmark->refresh();
        $this->assertFalse($bookmark->is_read);
        $this->assertNull($bookmark->read_at);
    }

    public function test_user_can_add_notes_to_bookmark(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $readerToken = 'test-reader-token';
        $bookmark = Bookmark::create([
            'reader_token' => $readerToken,
            'post_id' => $post->id,
        ]);

        $notes = 'This is a great article about Laravel testing!';

        $response = $this->postJsonWithCookie("/bookmarks/{$bookmark->id}/notes", ['notes' => $notes], $readerToken);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'notes' => $notes,
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'notes' => $notes,
        ]);
    }

    public function test_user_can_update_existing_notes(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $readerToken = 'test-reader-token';
        $bookmark = Bookmark::create([
            'reader_token' => $readerToken,
            'post_id' => $post->id,
            'notes' => 'Original notes',
        ]);

        $newNotes = 'Updated notes with more information';

        $response = $this->postJsonWithCookie("/bookmarks/{$bookmark->id}/notes", ['notes' => $newNotes], $readerToken);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'notes' => $newNotes,
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'notes' => $newNotes,
        ]);
    }

    public function test_user_can_clear_notes(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $readerToken = 'test-reader-token';
        $bookmark = Bookmark::create([
            'reader_token' => $readerToken,
            'post_id' => $post->id,
            'notes' => 'Some notes to be cleared',
        ]);

        $response = $this->postJsonWithCookie("/bookmarks/{$bookmark->id}/notes", ['notes' => null], $readerToken);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'notes' => null,
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'notes' => null,
        ]);
    }

    public function test_unauthorized_user_cannot_modify_bookmark(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $bookmark = Bookmark::create([
            'reader_token' => 'owner-token',
            'post_id' => $post->id,
        ]);

        $response = $this->postJsonWithCookie("/bookmarks/{$bookmark->id}/read", [], 'different-token');

        $response->assertForbidden();
    }

    public function test_bookmarks_can_be_filtered_by_read_status(): void
    {
        $readerToken = 'test-reader-token';

        $post1 = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
            'title' => 'Read Post Title Unique ABC123',
        ]);
        $post2 = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
            'title' => 'Unread Post Title Unique XYZ789',
        ]);

        Bookmark::create([
            'reader_token' => $readerToken,
            'post_id' => $post1->id,
            'is_read' => true,
        ]);

        Bookmark::create([
            'reader_token' => $readerToken,
            'post_id' => $post2->id,
            'is_read' => false,
        ]);

        // Filter for unread bookmarks - should only show unread
        $response = $this->getWithCookie('/bookmarks?status=unread', $readerToken);
        $response->assertOk();

        $unreadBookmarks = Bookmark::where('reader_token', $readerToken)
            ->where('is_read', false)
            ->count();
        $this->assertEquals(1, $unreadBookmarks);

        // Filter for read bookmarks - should only show read
        $response = $this->getWithCookie('/bookmarks?status=read', $readerToken);
        $response->assertOk();

        $readBookmarks = Bookmark::where('reader_token', $readerToken)
            ->where('is_read', true)
            ->count();
        $this->assertEquals(1, $readBookmarks);
    }

    public function test_notes_validation_enforces_max_length(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $readerToken = 'test-reader-token';
        $bookmark = Bookmark::create([
            'reader_token' => $readerToken,
            'post_id' => $post->id,
        ]);

        $tooLongNotes = str_repeat('a', 5001);

        $response = $this->postJsonWithCookie("/bookmarks/{$bookmark->id}/notes", ['notes' => $tooLongNotes], $readerToken);

        $response->assertStatus(422);
    }
}
