<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_mark_read_mark_all_delete_and_preferences(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->unread()->create(['user_id' => $user->id]);

        // List
        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');
        $list->assertOk();
        $this->assertGreaterThanOrEqual(3, $list->json('total'));

        // Unread count
        $unread = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications/unread');
        $unread->assertOk();
        $this->assertSame(3, $unread->json('count'));

        // Mark one as read
        $notificationId = Notification::where('user_id', $user->id)->first()->id;
        $mark = $this->actingAs($user, 'sanctum')->postJson('/api/v1/notifications/'.$notificationId.'/read');
        $mark->assertOk();
        $this->assertNotNull(Notification::find($notificationId)->read_at);

        // Mark all
        $markAll = $this->actingAs($user, 'sanctum')->postJson('/api/v1/notifications/read-all');
        $markAll->assertOk();
        $this->assertSame(0, Notification::where('user_id', $user->id)->unread()->count());

        // Preferences get
        $prefs = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications/preferences');
        $prefs->assertOk();
        $this->assertArrayHasKey('email_enabled', $prefs->json());

        // Update preferences
        $update = $this->actingAs($user, 'sanctum')->putJson('/api/v1/notifications/preferences', [
            'email_enabled' => false,
            'digest_frequency' => 'daily',
        ]);
        $update->assertOk();
        $prefs2 = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications/preferences');
        $this->assertFalse($prefs2->json('email_enabled'));

        // Delete a notification
        $toDelete = Notification::where('user_id', $user->id)->first();
        $del = $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/notifications/'.$toDelete->id);
        $del->assertNoContent();
        $this->assertDatabaseMissing('notifications', ['id' => $toDelete->id]);
    }
}
