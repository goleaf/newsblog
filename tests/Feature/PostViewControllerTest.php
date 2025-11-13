<?php

namespace Tests\Feature;

use App\Http\Controllers\PostViewController;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PostViewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_view_creates_record_and_increments_count(): void
    {
        $post = $this->createPublishedPost();
        $initialCount = $post->view_count;

        $this->trackView($post);

        $view = PostView::where('post_id', $post->id)->first();

        $this->assertNotNull($view);
        $this->assertNotEmpty($view->session_id);
        $this->assertEquals($initialCount + 1, $post->fresh()->view_count);
    }

    public function test_track_view_prevents_duplicates_for_same_session(): void
    {
        $post = $this->createPublishedPost();
        $initialCount = $post->view_count;

        $this->trackView($post);
        $this->trackView($post, resetSession: false);

        $this->assertEquals(1, PostView::where('post_id', $post->id)->count());
        $this->assertEquals($initialCount + 1, $post->fresh()->view_count);
    }

    public function test_track_view_accepts_multiple_sessions(): void
    {
        $post = $this->createPublishedPost();
        $initialCount = $post->view_count;

        $this->trackView($post);
        $this->trackView($post, resetSession: true);

        $this->assertEquals(2, PostView::where('post_id', $post->id)->count());
        $this->assertEquals($initialCount + 2, $post->fresh()->view_count);
    }

    protected function trackView(Post $post, bool $resetSession = true): void
    {
        $session = session();

        if ($resetSession && $session->isStarted()) {
            $session->invalidate();
        }

        if (! $session->isStarted()) {
            $session->start();
        }

        $request = Request::create('/post/'.$post->slug, 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'PHPUnit',
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_REFERER' => 'https://example.com',
        ]);

        app(PostViewController::class)->trackView($post, $request);
    }

    protected function createPublishedPost(): Post
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        return Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }
}
