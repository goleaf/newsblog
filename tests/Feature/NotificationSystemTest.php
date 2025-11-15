<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = app(NotificationService::class);
    }

    public function test_notification_dropdown_displays_unread_notifications(): void
    {
        $user = User::factory()->create();

        // Create some notifications
        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('notifications.unread'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'unread_count' => 3,
        ]);
        $response->assertJsonCount(3, 'notifications');
    }

    public function test_unread_count_badge_shows_correct_number(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(5)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $unreadCount = $this->notificationService->getUnreadCount($user);

        $this->assertEquals(5, $unreadCount);
    }

    public function test_mark_as_read_functionality(): void
    {
        $user = User::factory()->create();

        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $this->assertTrue($notification->isUnread());

        $response = $this->actingAs($user)
            ->postJson(route('notifications.read', $notification));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);

        $notification->refresh();
        $this->assertTrue($notification->isRead());
        $this->assertNotNull($notification->read_at);
    }

    public function test_mark_all_as_read_functionality(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(5)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $this->assertEquals(5, $this->notificationService->getUnreadCount($user));

        $response = $this->actingAs($user)
            ->postJson(route('notifications.read-all'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);

        $this->assertEquals(0, $this->notificationService->getUnreadCount($user));
    }

    public function test_notification_creation_for_comment_reply(): void
    {
        $user = User::factory()->create();
        $comment = \App\Models\Comment::factory()->create();
        $reply = \App\Models\Comment::factory()->create([
            'parent_id' => $comment->id,
        ]);

        $notification = $this->notificationService->notifyCommentReply($user, $comment, $reply);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals(Notification::TYPE_COMMENT_REPLY, $notification->type);
        $this->assertStringContainsString('replied to your comment', $notification->message);
        $this->assertNotNull($notification->action_url);
    }

    public function test_notification_creation_for_post_published(): void
    {
        $user = User::factory()->create();
        $post = \App\Models\Post::factory()->create();

        $notification = $this->notificationService->notifyPostPublished($user, $post);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals(Notification::TYPE_POST_PUBLISHED, $notification->type);
        $this->assertStringContainsString($post->title, $notification->message);
    }

    public function test_notification_creation_for_comment_approved(): void
    {
        $user = User::factory()->create();
        $comment = \App\Models\Comment::factory()->create();

        $notification = $this->notificationService->notifyCommentApproved($user, $comment);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals(Notification::TYPE_COMMENT_APPROVED, $notification->type);
        $this->assertStringContainsString('approved', $notification->message);
    }

    public function test_notification_creation_for_series_updated(): void
    {
        $user = User::factory()->create();
        $series = \App\Models\Series::factory()->create(['name' => 'Test Series']);
        $post = \App\Models\Post::factory()->create();

        $notification = $this->notificationService->notifySeriesUpdated($user, $series, $post);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals(Notification::TYPE_SERIES_UPDATED, $notification->type);
        $this->assertStringContainsString('Test Series', $notification->message);
    }

    public function test_user_cannot_mark_others_notification_as_read(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $notification = Notification::factory()->create([
            'user_id' => $user1->id,
        ]);

        $response = $this->actingAs($user2)
            ->postJson(route('notifications.read', $notification));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized',
        ]);
    }

    public function test_user_cannot_delete_others_notification(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $notification = Notification::factory()->create([
            'user_id' => $user1->id,
        ]);

        $response = $this->actingAs($user2)
            ->deleteJson(route('notifications.destroy', $notification));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized',
        ]);
    }

    public function test_notification_deletion(): void
    {
        $user = User::factory()->create();

        $notification = Notification::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(route('notifications.destroy', $notification));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Notification deleted',
        ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_notifications_index_page_displays_all_notifications(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(5)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
        $response->assertViewHas('notifications');
    }

    public function test_notifications_are_paginated(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(25)->create([
            'user_id' => $user->id,
        ]);

        $notifications = $this->notificationService->getAll($user, 20);

        $this->assertEquals(20, $notifications->count());
        $this->assertEquals(25, $notifications->total());
    }

    public function test_get_unread_notifications_limits_results(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(15)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $notifications = $this->notificationService->getUnread($user, 10);

        $this->assertEquals(10, $notifications->count());
    }
}
