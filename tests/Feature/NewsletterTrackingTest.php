<?php

namespace Tests\Feature;

use App\Models\NewsletterSend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_pixel_increments_opens_and_returns_png(): void
    {
        $send = NewsletterSend::factory()->create();

        $res = $this->get(route('newsletter.open', $send->id));
        $res->assertOk();
        $res->assertHeader('Content-Type', 'image/png');
        $this->assertSame(1, (int) \Illuminate\Support\Facades\Cache::get("newsletter:send:{$send->id}:opens"));
    }

    public function test_click_increments_clicks_and_redirects(): void
    {
        $send = NewsletterSend::factory()->create();
        $url = 'https://example.com/article';

        $res = $this->get(route('newsletter.click', ['sid' => $send->id, 'url' => $url]));
        $res->assertRedirect($url);
        $this->assertSame(1, (int) \Illuminate\Support\Facades\Cache::get("newsletter:send:{$send->id}:clicks"));
    }
}
