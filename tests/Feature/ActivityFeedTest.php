<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityService $activityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activityService = app(ActivityService::class);
    }

    public function test_user_can_view_their_activity_feed(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        // Create an activity
        $this->activityService->recordPostPublished($post);

        $response = $this->actingAs($user)
            ->get(route('activities.index'));

        $response->assertOk();
        $response->assertViewIs('activities.index');
        $response->assertViewHas('activities');
    }

    public function test_user_can_view_following_activity_feed(): void
    {
        $user = User::factory()->create();
        $followed = User::factory()->create();

        Follow::create([
            'follower_id' => $user->id,
            'followed_id' => $followed->id,
        ]);

        $post = Post::factory()->create(['user_id' => $followed->id]);
        $this->activityService->recordPostPublished($post);

        $response = $this->actingAs($user)
            ->get(route('activities.following'));

        $response->assertOk();
        $response->assertViewIs('activities.following');
        $response->assertViewHas('activities');
    }

    public function test_activity_feed_can_be_filtered_by_type(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->activityService->recordPostPublished($post);

        $response = $this->actingAs($user)
            ->get(route('activities.index', ['type' => 'published_post']));

        $response->assertOk();
        $response->assertViewHas('currentType', 'published_post');
    }

    public function test_activity_service_records_article_published(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $activity = $this->activityService->recordPostPublished($post);

        $this->assertDatabaseHas('activities', [
            'actor_id' => $user->id,
            'verb' => 'published_post',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
        ]);

        $this->assertEquals('published_post', $activity->verb);
    }

    public function test_activity_service_records_post_published(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $activity = $this->activityService->recordPostPublished($post);

        $this->assertDatabaseHas('activities', [
            'actor_id' => $user->id,
            'verb' => 'published_post',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
        ]);
    }

    public function test_activity_service_records_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $activity = $this->activityService->recordComment($comment);

        $this->assertDatabaseHas('activities', [
            'actor_id' => $user->id,
            'verb' => 'commented',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
        ]);
    }

    public function test_activity_service_records_bookmark(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $bookmark = Bookmark::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $activity = $this->activityService->recordBookmark($bookmark);

        $this->assertDatabaseHas('activities', [
            'actor_id' => $user->id,
            'verb' => 'bookmarked',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
        ]);
    }

    public function test_activity_service_records_follow(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();
        $follow = Follow::create([
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
        ]);

        $activity = $this->activityService->recordFollow($follow);

        $this->assertDatabaseHas('activities', [
            'actor_id' => $follower->id,
            'verb' => 'followed',
            'subject_type' => User::class,
            'subject_id' => $followed->id,
        ]);
    }

    public function test_activity_service_generates_user_activity_feed(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->activityService->recordPostPublished($post);

        $activities = $this->activityService->getUserActivityFeed($user, 20);

        $this->assertCount(1, $activities);
        $this->assertEquals('published_post', $activities->first()['verb']);
    }

    public function test_activity_service_generates_following_activity_feed(): void
    {
        $user = User::factory()->create();
        $followed = User::factory()->create();

        Follow::create([
            'follower_id' => $user->id,
            'followed_id' => $followed->id,
        ]);

        $post = Post::factory()->create(['user_id' => $followed->id]);
        $this->activityService->recordPostPublished($post);

        $activities = $this->activityService->getFollowingActivityFeed($user, 20);

        $this->assertCount(1, $activities);
        $this->assertEquals('published_post', $activities->first()['verb']);
    }

    public function test_activity_service_filters_by_type(): void
    {
        $user = User::factory()->create();
        $post1 = Post::factory()->create(['user_id' => $user->id]);
        $post2 = Post::factory()->create(['user_id' => $user->id]);

        $this->activityService->recordPostPublished($post1);

        // Create a different activity type
        $bookmark = Bookmark::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post2->id,
        ]);
        $this->activityService->recordBookmark($bookmark);

        $activities = $this->activityService->getUserActivityFeed($user, 20);
        $filtered = $this->activityService->filterByType($activities, 'published_post');

        $this->assertCount(1, $filtered);
        $this->assertEquals('published_post', $filtered->first()['verb']);
    }

    public function test_activity_service_aggregates_similar_activities(): void
    {
        $user = User::factory()->create();

        // Create multiple posts within a short time
        for ($i = 0; $i < 3; $i++) {
            $post = Post::factory()->create(['user_id' => $user->id]);
            $this->activityService->recordPostPublished($post);
        }

        $activities = $this->activityService->getUserActivityFeed($user, 20);
        $aggregated = $this->activityService->aggregateSimilarActivities($activities, 60);

        // Should aggregate into one activity
        $this->assertCount(1, $aggregated);
        $this->assertTrue($aggregated->first()['is_aggregated']);
        $this->assertEquals(3, $aggregated->first()['count']);
    }

    public function test_user_can_view_activity_detail(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $activity = $this->activityService->recordPostPublished($post);

        $response = $this->actingAs($user)
            ->get(route('activities.show', $activity->id));

        $response->assertOk();
        $response->assertViewIs('activities.show');
        $response->assertViewHas('activity');
    }

    public function test_guest_cannot_view_activity_feed(): void
    {
        $response = $this->get(route('activities.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_view_following_activity_feed(): void
    {
        $response = $this->get(route('activities.following'));

        $response->assertRedirect(route('login'));
    }
}
