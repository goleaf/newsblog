<?php

namespace Tests\Feature;

use App\Jobs\SendNewsletterJob;
use App\Models\Newsletter;
use App\Models\NewsletterSend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class NewsletterJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_newsletter_job_marks_send_as_sent(): void
    {
        $subscriber = Newsletter::factory()->verified()->create();
        $send = NewsletterSend::factory()->create([
            'subscriber_id' => $subscriber->id,
            'status' => 'queued',
            'sent_at' => null,
        ]);

        (new SendNewsletterJob($send->id))->handle();

        $this->assertDatabaseHas('newsletter_sends', [
            'id' => $send->id,
            'status' => 'sent',
        ]);
    }

    public function test_send_newsletters_command_queues_jobs_for_verified(): void
    {
        Bus::fake();

        Newsletter::factory()->count(2)->verified()->create();
        Newsletter::factory()->pending()->create();
        Newsletter::factory()->unsubscribed()->create();

        $expected = \App\Models\Newsletter::verified()->count();
        $this->artisan('newsletters:send --subject="Test" --content="<p>Hi</p>"')
            ->assertExitCode(0);

        $this->assertGreaterThanOrEqual(2, Bus::dispatched(SendNewsletterJob::class)->count());
        $this->assertDatabaseCount('newsletter_sends', $expected);
    }
}
