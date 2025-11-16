<?php

namespace App\Jobs;

use App\Enums\NewsletterStatus;
use App\Models\Newsletter;
use App\Models\NewsletterSend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNewsletterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $sendId) {}

    public function handle(): void
    {
        $send = NewsletterSend::find($this->sendId);
        if (! $send) {
            return;
        }

        // Resolve subscriber
        $subscriber = Newsletter::find($send->subscriber_id);
        if (! $subscriber || $subscriber->status !== NewsletterStatus::Subscribed) {
            $send->update([
                'status' => 'failed',
                'error' => 'Subscriber not eligible',
            ]);

            return;
        }

        // In a real implementation, send via mail provider here.
        // For now, mark as sent and timestamp for analytics.
        $send->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider_message_id' => $send->provider_message_id ?? (string) str()->uuid(),
        ]);

        Log::info('Newsletter send processed', ['send_id' => $send->id]);
    }
}
