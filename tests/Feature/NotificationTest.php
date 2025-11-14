<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = app(NotificationService::class);
    }

    public function test_user_can_view_notifications_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewIs('notifications.index');
    }

    public function test_guest_cannot_view_notifications_page(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_notification_service_creates_notification(): void
    {
        $user = User::factory()->create();

        $notification = $this->notificationService->create(
            user: $user,
            type: Notification::TYPE_COMMENT_REPLY,
            title: 'Test Notification',
            message: 'This is a test notification',
            actionUrl: '/test',
            icon: 'bell'
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals('Test Notification', $notification->title);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Test Notification',
        ]);
    }

    public function test_user_can_get_unread_notifications(): void
    {
        $user = User::factory()->create();

        // Create some notifications
        Notification::factory()->count(3)->create(['user_id' => $user->id]);
        Notification::factory()->count(2)->create(['user_id' => $user->id, 'read_at' => now()]);

        $response = $this->actingAs($user)->getJson(route('notifications.unread'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'notifications',
            'unread_count',
        ]);
        $response->assertJson([
            'success' => true,
            'unread_count' => 3,
        ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)->postJson(
            route('notifications.read', $notification)
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_others_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->postJson(
            route('notifications.read', $notification)
        );

        $response->assertForbidden();
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(5)->create(['user_id' => $user->id]);

        $this->assertEquals(5, $user->notifications()->unread()->count());

        $response = $this->actingAs($user)->postJson(route('notifications.read-all'));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertEquals(0, $user->notifications()->unread()->count());
    }

    public function test_user_can_delete_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson(
            route('notifications.destroy', $notification)
        );

        $response->assertOk();
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_user_cannot_delete_others_notification(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->deleteJson(
            route('notifications.destroy', $notification)
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    public function test_notification_service_creates_comment_reply_notification(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
        $reply = Comment::factory()->create([
            'post_id' => $post->id,
            'parent_id' => $comment->id,
        ]);

        $notification = $this->notificationService->notifyCommentReply($user, $comment, $reply);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals(Notification::TYPE_COMMENT_REPLY, $notification->type);
        $this->assertEquals($user->id, $notification->user_id);
        $this->assertStringContainsString('replied to your comment', $notification->message);
    }

    public function test_user_email_preferences_are_saved(): void
    {
        $user = User::factory()->create();

        // Simulate form submission with checkboxes
        $response = $this->actingAs($user)->patch(route('profile.email-preferences'), [
            'preferences' => [
                'comment_replies' => '1', // Checked
                // post_published not included (unchecked)
                'frequency' => 'daily',
            ],
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'email-preferences-updated');

        $user->refresh();
        $savedPreferences = $user->getEmailPreferences();
        $this->assertTrue($savedPreferences['comment_replies']);
        $this->assertFalse($savedPreferences['post_published']);
        $this->assertEquals('daily', $savedPreferences['frequency']);
    }

    public function test_user_wants_email_notification_check(): void
    {
        $user = User::factory()->create([
            'email_preferences' => [
                'comment_replies' => true,
                'post_published' => false,
            ],
        ]);

        $this->assertTrue($user->wantsEmailNotification('comment_replies'));
        $this->assertFalse($user->wantsEmailNotification('post_published'));
    }
}
