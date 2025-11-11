<?php

namespace Tests\Feature;

use App\Jobs\SendPostPublishedNotification;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PublishScheduledPostsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_publishes_scheduled_posts_that_are_ready(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a scheduled post that's ready to publish
        $readyPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
            'published_at' => null,
        ]);

        // Create a scheduled post that's not ready yet
        $futurePost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'published_at' => null,
        ]);

        $this->artisan('posts:publish-scheduled')
            ->expectsOutput('Published 1 scheduled post(s).')
            ->assertExitCode(0);

        // Verify the ready post was published
        $readyPost->refresh();
        $this->assertEquals('published', $readyPost->status);
        $this->assertNotNull($readyPost->published_at);

        // Verify the future post was not published
        $futurePost->refresh();
        $this->assertEquals('scheduled', $futurePost->status);
        $this->assertNull($futurePost->published_at);
    }

    public function test_command_dispatches_notification_job_for_published_posts(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('posts:publish-scheduled')
            ->assertExitCode(0);

        Queue::assertPushed(SendPostPublishedNotification::class, function ($job) use ($post) {
            return $job->post->id === $post->id;
        });
    }

    public function test_command_handles_no_scheduled_posts(): void
    {
        $this->artisan('posts:publish-scheduled')
            ->expectsOutput('No scheduled posts ready to publish.')
            ->assertExitCode(0);
    }

    public function test_command_publishes_multiple_scheduled_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create multiple scheduled posts that are ready
        Post::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('posts:publish-scheduled')
            ->expectsOutput('Published 3 scheduled post(s).')
            ->assertExitCode(0);

        $this->assertEquals(3, Post::where('status', 'published')->count());
    }

    public function test_command_sets_published_at_to_scheduled_at_time(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $scheduledTime = now()->subHour();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'scheduled',
            'scheduled_at' => $scheduledTime,
        ]);

        $this->artisan('posts:publish-scheduled')
            ->assertExitCode(0);

        $post->refresh();
        $this->assertEquals('published', $post->status);
        $this->assertEquals($scheduledTime->timestamp, $post->published_at->timestamp);
    }
}
