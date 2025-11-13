<?php

namespace Tests\Unit;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_can_be_created_without_attributes(): void
    {
        $notification = new Notification;
        $notification->save();

        $this->assertNotNull($notification->id);
        $this->assertNotNull($notification->created_at);
        $this->assertNotNull($notification->updated_at);
    }

    public function test_notification_timestamps_can_be_touched(): void
    {
        $notification = new Notification;
        $notification->save();

        $createdAt = $notification->created_at;

        $this->travel(60)->seconds();
        $notification->touch();

        $this->assertNotEquals($createdAt, $notification->fresh()->updated_at);
    }
}
