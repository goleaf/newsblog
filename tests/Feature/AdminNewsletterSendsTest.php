<?php

namespace Tests\Feature;

use App\Models\Newsletter;
use App\Models\NewsletterSend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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

    public function test_admin_can_resend_send(): void
    {
        Bus::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $subscriber = Newsletter::factory()->verified()->create();
        $send = NewsletterSend::factory()->create([
            'subscriber_id' => $subscriber->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $res = $this->actingAs($admin)->post(route('admin.newsletters.sends.resend', $send));
        $res->assertRedirect();

        $send->refresh();
        $this->assertEquals('queued', $send->status);
        Bus::assertDispatched(\App\Jobs\SendNewsletterJob::class);
    }

    public function test_admin_sends_listing_displays_aggregated_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subscriber = Newsletter::factory()->verified()->create();

        // Create a few sends with different statuses
        $sent1 = NewsletterSend::factory()->create(['subscriber_id' => $subscriber->id, 'status' => 'sent', 'sent_at' => now()]);
        $sent2 = NewsletterSend::factory()->create(['subscriber_id' => $subscriber->id, 'status' => 'sent', 'sent_at' => now()]);
        $queued = NewsletterSend::factory()->create(['subscriber_id' => $subscriber->id, 'status' => 'queued']);

        // Seed cache metrics for recent sends
        Cache::put("newsletter:send:{$sent1->id}:opens", 3, 3600);
        Cache::put("newsletter:send:{$sent1->id}:clicks", 1, 3600);
        Cache::put("newsletter:send:{$sent2->id}:opens", 2, 3600);
        Cache::put("newsletter:send:{$sent2->id}:clicks", 2, 3600);

        $res = $this->actingAs($admin)->get(route('admin.newsletters.sends'));
        $res->assertOk();

        // Verify summary labels appear
        $res->assertSee('Total Sends');
        $res->assertSee('Sent / Queued');
        $res->assertSee('Opens / Clicks');
        $res->assertSee('CTR (Clicks / Sent)');

        // Verify numbers roughly match
        $res->assertSee((string) 3); // total sends (at least 3 created here)
        $res->assertSee((string) 2); // sent count
        $res->assertSee((string) 1); // queued count
        $res->assertSee((string) 5); // opens total (3 + 2)
        $res->assertSee((string) 3); // clicks total (1 + 2)

        // Batches section
        $res->assertSee('Recent Batches');
        $res->assertSee((string) $sent1->batch_id);
    }
}
