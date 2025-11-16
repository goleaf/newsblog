<?php

namespace Tests\Unit;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_scopes_filter_by_status(): void
    {
        ContactMessage::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'subject' => 'New inquiry',
            'message' => 'Hello there',
            'status' => 'new',
        ]);

        ContactMessage::create([
            'name' => 'Read User',
            'email' => 'read@example.com',
            'subject' => 'Read inquiry',
            'message' => 'Hi',
            'status' => 'read',
        ]);

        ContactMessage::create([
            'name' => 'Replied User',
            'email' => 'replied@example.com',
            'subject' => 'Replied inquiry',
            'message' => 'Thanks',
            'status' => 'replied',
        ]);

        $this->assertCount(1, ContactMessage::new()->get());
        $this->assertCount(1, ContactMessage::read()->get());
        $this->assertCount(1, ContactMessage::replied()->get());
    }

    public function test_status_helpers_update_status_column(): void
    {
        $message = ContactMessage::create([
            'name' => 'Support',
            'email' => 'support@example.com',
            'subject' => 'Need help',
            'message' => 'Help me',
            'status' => 'new',
        ]);

        $message->markAsRead();

        $this->assertEquals('read', $message->fresh()->status);

        $message->markAsReplied();

        $this->assertEquals('replied', $message->fresh()->status);
    }
}
