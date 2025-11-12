<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Nova\Category as CategoryResource;
use App\Nova\Comment as CommentResource;
use App\Nova\Media as MediaResource;
use App\Nova\Post as PostResource;
use App\Nova\Tag as TagResource;
use App\Nova\User as UserResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class NovaActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a mock NovaRequest for testing.
     */
    protected function createNovaRequest(User $user, string $ip = '127.0.0.1', string $userAgent = 'Test Agent'): NovaRequest
    {
        $request = NovaRequest::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ]);

        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_post_created_via_nova_logs_activity(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $post = Post::factory()->make([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $post->save();

        $request = $this->createNovaRequest($user, '192.168.1.1', 'Mozilla/5.0');
        PostResource::afterCreate($request, $post);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'Nova',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
            'event' => 'created',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ]);

        $log = ActivityLog::where('subject_id', $post->id)
            ->where('log_name', 'Nova')
            ->where('event', 'created')
            ->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Post created via Nova', $log->description);
        $this->assertArrayHasKey('attributes', $log->properties);
    }

    public function test_post_updated_via_nova_logs_activity_with_changes(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Original Title',
        ]);

        $post->title = 'Updated Title';
        $post->save();

        $request = $this->createNovaRequest($user, '10.0.0.1', 'Chrome/91.0');
        PostResource::afterUpdate($request, $post);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'Nova',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
            'event' => 'updated',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Chrome/91.0',
        ]);

        $log = ActivityLog::where('subject_id', $post->id)
            ->where('log_name', 'Nova')
            ->where('event', 'updated')
            ->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Post updated via Nova', $log->description);
        $this->assertArrayHasKey('old', $log->properties);
        $this->assertArrayHasKey('new', $log->properties);
    }

    public function test_post_deleted_via_nova_logs_activity(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $request = $this->createNovaRequest($user);
        PostResource::afterDelete($request, $post);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'Nova',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
            'event' => 'deleted',
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $log = ActivityLog::where('subject_id', $post->id)->where('event', 'deleted')->first();
        $this->assertStringContainsString('Post deleted via Nova', $log->description);
    }

    public function test_category_created_via_nova_logs_activity(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $request = $this->createNovaRequest($user);
        CategoryResource::afterCreate($request, $category);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'Nova',
            'subject_type' => Category::class,
            'subject_id' => $category->id,
            'event' => 'created',
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
    }

    public function test_category_updated_via_nova_logs_activity(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create(['name' => 'Original Name']);
        $category->name = 'Updated Name';
        $category->save();

        $request = $this->createNovaRequest($user);
        CategoryResource::afterUpdate($request, $category);

        $log = ActivityLog::where('subject_id', $category->id)->where('event', 'updated')->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('old', $log->properties);
        $this->assertArrayHasKey('new', $log->properties);
    }

    public function test_tag_created_via_nova_logs_activity(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $tag = Tag::factory()->create();

        $request = $this->createNovaRequest($user, '172.16.0.1', 'Firefox/89.0');
        TagResource::afterCreate($request, $tag);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'Nova',
            'subject_type' => Tag::class,
            'subject_id' => $tag->id,
            'event' => 'created',
            'ip_address' => '172.16.0.1',
            'user_agent' => 'Firefox/89.0',
        ]);
    }

    public function test_tag_deleted_via_nova_logs_activity(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $tag = Tag::factory()->create();

        $request = $this->createNovaRequest($user);
        TagResource::afterDelete($request, $tag);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'Nova',
            'subject_type' => Tag::class,
            'subject_id' => $tag->id,
            'event' => 'deleted',
        ]);
    }

    public function test_comment_created_via_nova_logs_activity(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $request = $this->createNovaRequest($user);
        CommentResource::afterCreate($request, $comment);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'Nova',
            'subject_type' => Comment::class,
            'subject_id' => $comment->id,
            'event' => 'created',
        ]);
    }

    public function test_comment_restored_via_nova_logs_activity(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $comment->delete();

        $comment->restore();

        $request = $this->createNovaRequest($user);
        CommentResource::afterRestore($request, $comment);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'Nova',
            'subject_type' => Comment::class,
            'subject_id' => $comment->id,
            'event' => 'restored',
        ]);

        $log = ActivityLog::where('subject_id', $comment->id)->where('event', 'restored')->first();
        $this->assertArrayHasKey('restored_at', $log->properties);
    }

    public function test_media_created_via_nova_logs_activity(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $media = Media::factory()->create(['user_id' => $user->id]);

        $request = $this->createNovaRequest($user);
        MediaResource::afterCreate($request, $media);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'Nova',
            'subject_type' => Media::class,
            'subject_id' => $media->id,
            'event' => 'created',
        ]);
    }

    public function test_media_force_deleted_via_nova_logs_activity(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $media = Media::factory()->create(['user_id' => $user->id]);
        $mediaId = $media->id;
        $media->delete();

        $request = $this->createNovaRequest($user);
        MediaResource::afterForceDelete($request, $media);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'Nova',
            'subject_type' => Media::class,
            'subject_id' => $mediaId,
            'event' => 'force_deleted',
        ]);

        $log = ActivityLog::where('subject_id', $mediaId)->where('event', 'force_deleted')->first();
        $this->assertStringContainsString('Media force deleted via Nova', $log->description);
    }

    public function test_user_created_via_nova_logs_activity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $newUser = User::factory()->create();

        $request = $this->createNovaRequest($admin);
        UserResource::afterCreate($request, $newUser);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'Nova',
            'subject_type' => User::class,
            'subject_id' => $newUser->id,
            'event' => 'created',
            'causer_type' => User::class,
            'causer_id' => $admin->id,
        ]);
    }

    public function test_activity_log_captures_ip_address(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        $customIp = '203.0.113.42';

        $request = $this->createNovaRequest($user, $customIp);
        PostResource::afterCreate($request, $post);

        $log = ActivityLog::where('subject_id', $post->id)
            ->where('log_name', 'Nova')
            ->where('event', 'created')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals($customIp, $log->ip_address);
    }

    public function test_activity_log_captures_user_agent(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        $customUserAgent = 'Custom User Agent String';

        $request = $this->createNovaRequest($user, '127.0.0.1', $customUserAgent);
        PostResource::afterCreate($request, $post);

        $log = ActivityLog::where('subject_id', $post->id)
            ->where('log_name', 'Nova')
            ->where('event', 'created')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals($customUserAgent, $log->user_agent);
    }

    public function test_activity_log_captures_user_when_authenticated(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();

        $request = $this->createNovaRequest($user);
        PostResource::afterCreate($request, $post);

        $log = ActivityLog::where('subject_id', $post->id)
            ->where('log_name', 'Nova')
            ->where('event', 'created')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals(User::class, $log->causer_type);
        $this->assertEquals($user->id, $log->causer_id);
    }

    public function test_archive_activity_logs_command_deletes_old_logs(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $oldDate = now()->subDays(91);
        $recentDate = now()->subDays(30);

        // Create old log (91 days ago)
        $oldLog = ActivityLog::factory()->create([
            'subject_type' => Post::class,
            'subject_id' => $post->id,
            'created_at' => $oldDate,
        ]);

        // Create recent log (30 days ago)
        $recentLog = ActivityLog::factory()->create([
            'subject_type' => Post::class,
            'subject_id' => $post->id,
            'created_at' => $recentDate,
        ]);

        $this->artisan('activity-logs:archive', ['--days' => 90])
            ->assertSuccessful();

        // Old log should be deleted
        $this->assertDatabaseMissing('activity_logs', [
            'id' => $oldLog->id,
        ]);

        // Recent log should remain
        $this->assertDatabaseHas('activity_logs', [
            'id' => $recentLog->id,
        ]);
    }

    public function test_archive_activity_logs_command_with_custom_days(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $oldDate = now()->subDays(60);
        $recentDate = now()->subDays(30);

        // Create log 60 days ago
        $oldLog = ActivityLog::factory()->create([
            'subject_type' => Post::class,
            'subject_id' => $post->id,
            'created_at' => $oldDate,
        ]);

        // Create log 30 days ago
        $recentLog = ActivityLog::factory()->create([
            'subject_type' => Post::class,
            'subject_id' => $post->id,
            'created_at' => $recentDate,
        ]);

        $this->artisan('activity-logs:archive', ['--days' => 45])
            ->assertSuccessful();

        // 60-day-old log should be deleted
        $this->assertDatabaseMissing('activity_logs', [
            'id' => $oldLog->id,
        ]);

        // 30-day-old log should remain
        $this->assertDatabaseHas('activity_logs', [
            'id' => $recentLog->id,
        ]);
    }

    public function test_archive_activity_logs_command_validates_days_parameter(): void
    {
        $this->artisan('activity-logs:archive', ['--days' => 0])
            ->assertFailed();

        $this->artisan('activity-logs:archive', ['--days' => -1])
            ->assertFailed();
    }
}
