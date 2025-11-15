<?php

namespace Tests\Feature;

use App\Http\Controllers\PostViewController;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
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

    public function test_track_view_respects_dnt_header_with_value_1(): void
    {
        $post = $this->createPublishedPost();
        $initialCount = $post->view_count;

        $this->trackView($post, dntHeader: '1');

        $this->assertEquals(0, PostView::where('post_id', $post->id)->count());
        $this->assertEquals($initialCount, $post->fresh()->view_count);
    }

    public function test_track_view_respects_dnt_header_with_value_yes(): void
    {
        $post = $this->createPublishedPost();
        $initialCount = $post->view_count;

        $this->trackView($post, dntHeader: 'yes');

        $this->assertEquals(0, PostView::where('post_id', $post->id)->count());
        $this->assertEquals($initialCount, $post->fresh()->view_count);
    }

    public function test_track_view_respects_lowercase_dnt_header(): void
    {
        $post = $this->createPublishedPost();
        $initialCount = $post->view_count;

        $session = session();
        $session->start();

        $request = Request::create('/post/'.$post->slug, 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'PHPUnit',
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_REFERER' => 'https://example.com',
            'HTTP_dnt' => '1',
        ]);

        app(PostViewController::class)->trackView($post, $request);

        $this->assertEquals(0, PostView::where('post_id', $post->id)->count());
        $this->assertEquals($initialCount, $post->fresh()->view_count);
    }

    public function test_track_view_allows_tracking_when_dnt_is_0(): void
    {
        $post = $this->createPublishedPost();
        $initialCount = $post->view_count;

        $this->trackView($post, dntHeader: '0');

        $this->assertEquals(1, PostView::where('post_id', $post->id)->count());
        $this->assertEquals($initialCount + 1, $post->fresh()->view_count);
    }

    public function test_track_view_allows_tracking_when_dnt_is_no(): void
    {
        $post = $this->createPublishedPost();
        $initialCount = $post->view_count;

        $this->trackView($post, dntHeader: 'no');

        $this->assertEquals(1, PostView::where('post_id', $post->id)->count());
        $this->assertEquals($initialCount + 1, $post->fresh()->view_count);
    }

    public function test_track_view_allows_tracking_when_dnt_header_is_absent(): void
    {
        $post = $this->createPublishedPost();
        $initialCount = $post->view_count;

        $this->trackView($post, dntHeader: null);

        $this->assertEquals(1, PostView::where('post_id', $post->id)->count());
        $this->assertEquals($initialCount + 1, $post->fresh()->view_count);
    }

    protected function trackView(Post $post, bool $resetSession = true, ?string $dntHeader = null): void
    {
        $session = session();

        if ($resetSession && $session->isStarted()) {
            $session->invalidate();
        }

        if (! $session->isStarted()) {
            $session->start();
        }

        $serverParams = [
            'HTTP_USER_AGENT' => 'PHPUnit',
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_REFERER' => 'https://example.com',
        ];

        if ($dntHeader !== null) {
            $serverParams['HTTP_DNT'] = $dntHeader;
        }

        $request = Request::create('/post/'.$post->slug, 'GET', [], [], [], $serverParams);

        app(PostViewController::class)->trackView($post, $request);
    }

    public function test_track_view_dispatches_job_for_async_processing(): void
    {
        Queue::fake();

        $post = $this->createPublishedPost();

        $this->trackView($post);

        Queue::assertPushed(\App\Jobs\TrackPostView::class, function ($job) use ($post) {
            return $job->postId === $post->id;
        });
    }

    public function test_track_view_handles_missing_user_agent_gracefully(): void
    {
        $post = $this->createPublishedPost();
        $session = session();
        $session->start();

        $request = Request::create('/post/'.$post->slug, 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            // No HTTP_USER_AGENT
        ]);

        app(PostViewController::class)->trackView($post, $request);

        $this->assertEquals(1, PostView::where('post_id', $post->id)->count());
    }

    public function test_track_view_handles_missing_referer_gracefully(): void
    {
        $post = $this->createPublishedPost();
        $session = session();
        $session->start();

        $request = Request::create('/post/'.$post->slug, 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'PHPUnit',
            'REMOTE_ADDR' => '127.0.0.1',
            // No HTTP_REFERER
        ]);

        app(PostViewController::class)->trackView($post, $request);

        $this->assertEquals(1, PostView::where('post_id', $post->id)->count());
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
