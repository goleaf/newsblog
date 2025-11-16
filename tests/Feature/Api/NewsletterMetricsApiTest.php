<?php

namespace Tests\Feature\Api;

use App\Models\Newsletter;
use App\Models\NewsletterSend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NewsletterMetricsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_newsletter_send_metrics(): void
    {
        $user = User::factory()->create();
        $subscriber = Newsletter::factory()->verified()->create();
        $send = NewsletterSend::factory()->create([
            'subscriber_id' => $subscriber->id,
        ]);

        Cache::put("newsletter:send:{$send->id}:opens", 5, 3600);
        Cache::put("newsletter:send:{$send->id}:clicks", 3, 3600);
        Cache::put("newsletter:send:{$send->id}:last_opened_at", now()->toIso8601String(), 3600);
        Cache::put("newsletter:send:{$send->id}:last_clicked_at", now()->toIso8601String(), 3600);

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/newsletters/sends/'.$send->id.'/metrics');
        $res->assertOk();
        $this->assertSame(5, $res->json('opens'));
        $this->assertSame(3, $res->json('clicks'));
    }
}
