<?php

namespace Tests\Feature;

use App\Models\Newsletter;
use App\Models\NewsletterSend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminNewsletterSendsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_send_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subscriber = Newsletter::factory()->verified()->create();
        $send = NewsletterSend::factory()->create([
            'subscriber_id' => $subscriber->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        Cache::put("newsletter:send:{$send->id}:opens", 2, 3600);
        Cache::put("newsletter:send:{$send->id}:clicks", 1, 3600);

        $res = $this->actingAs($admin)->get(route('admin.newsletters.sends.show', $send));
        $res->assertOk();
        $res->assertSee((string) $send->id);
        $res->assertSee($subscriber->email);
        $res->assertSee('Opens');
        $res->assertSee('Clicks');
    }
}
