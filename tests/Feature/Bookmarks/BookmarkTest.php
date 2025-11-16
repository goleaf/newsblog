<?php

namespace Tests\Feature\Bookmarks;

use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_bookmark_creation(): void
    {
        $post = Post::factory()->create(['status' => \App\Enums\PostStatus::Published, 'published_at' => now()]);
        $token = Str::uuid()->toString();

        $response = $this->withCookie('reader_token', $token)
            ->postJson(route('bookmarks.toggle'), ['post_id' => $post->id]);

        $response->assertOk()
            ->assertJson(['bookmarked' => true]);

        $this->assertDatabaseHas('bookmarks', [
            'reader_token' => $token,
            'post_id' => $post->id,
        ]);
    }

    public function test_duplicate_prevention(): void
    {
        $post = Post::factory()->create(['status' => \App\Enums\PostStatus::Published, 'published_at' => now()]);
        $token = Str::uuid()->toString();

        // First toggle => create
        $this->withCookie('reader_token', $token)
            ->postJson(route('bookmarks.toggle'), ['post_id' => $post->id])
            ->assertOk()
            ->assertJson(['bookmarked' => true]);

        // Second store explicitly should not create duplicate
        $this->withCookie('reader_token', $token)
            ->postJson(route('bookmarks.store'), ['post_id' => $post->id])
            ->assertOk()
            ->assertJson(['bookmarked' => true]);

        $this->assertSame(1, Bookmark::query()->where('reader_token', $token)->where('post_id', $post->id)->count());
    }

    public function test_bookmark_removal(): void
    {
        $post = Post::factory()->create(['status' => \App\Enums\PostStatus::Published, 'published_at' => now()]);
        $token = Str::uuid()->toString();

        $this->withCookie('reader_token', $token)
            ->postJson(route('bookmarks.toggle'), ['post_id' => $post->id])
            ->assertOk();

        $this->withCookie('reader_token', $token)
            ->deleteJson(route('bookmarks.destroy'), ['post_id' => $post->id])
            ->assertOk()
            ->assertJson(['bookmarked' => false]);

        $this->assertDatabaseMissing('bookmarks', [
            'reader_token' => $token,
            'post_id' => $post->id,
        ]);
    }

    public function test_reading_list_display(): void
    {
        $posts = Post::factory()->count(3)->create(['status' => \App\Enums\PostStatus::Published, 'published_at' => now()]);
        $token = Str::uuid()->toString();

        foreach ($posts as $post) {
            Bookmark::create([
                'reader_token' => $token,
                'post_id' => $post->id,
            ]);
        }

        $response = $this->withCookie('reader_token', $token)->get(route('bookmarks.index'));
        $response->assertOk();
        foreach ($posts as $post) {
            $response->assertSee(e($post->title));
        }
    }
}


