<?php

namespace Tests\Feature;

use App\Models\Newsletter;
use App\Models\NewsletterSend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminNewsletterSendsFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_by_batch_and_date_range_and_exports_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subscriber = Newsletter::factory()->verified()->create();

        // Old batch
        $old = NewsletterSend::factory()->create([
            'subscriber_id' => $subscriber->id,
            'batch_id' => '202401010900',
            'status' => 'sent',
            'created_at' => now()->subMonths(6),
            'sent_at' => now()->subMonths(6),
        ]);
        Cache::put("newsletter:send:{$old->id}:opens", 1, 3600);

        // Recent batch
        $recent1 = NewsletterSend::factory()->create([
            'subscriber_id' => $subscriber->id,
            'batch_id' => '202511160900',
            'status' => 'sent',
            'created_at' => now()->subDay(),
            'sent_at' => now()->subDay(),
        ]);
        $recent2 = NewsletterSend::factory()->create([
            'subscriber_id' => $subscriber->id,
            'batch_id' => '202511160900',
            'status' => 'queued',
            'created_at' => now()->subDay(),
        ]);
        Cache::put("newsletter:send:{$recent1->id}:opens", 2, 3600);
        Cache::put("newsletter:send:{$recent1->id}:clicks", 1, 3600);

        // Filter list view by recent batch
        $res = $this->actingAs($admin)->get(route('admin.newsletters.sends', [
            'batch_id' => '202511160900',
            'from' => now()->subDays(2)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $res->assertOk();
        $res->assertSee('202511160900');
        $res->assertDontSee('202401010900');

        // Export CSV for same filters
        $csv = $this->actingAs($admin)->get(route('admin.newsletters.sends.export', [
            'batch_id' => '202511160900',
            'from' => now()->subDays(2)->toDateString(),
            'to' => now()->toDateString(),
        ]));
        $csv->assertOk();
        // Symfony appends charset to text/* content types; accept full header value
        $csv->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv->assertSee('batch_id,total,sent,queued,failed,opens,clicks,ctr');
        $csv->assertSee('202511160900');
    }
}
