<?php

namespace App\Console\Commands;

use App\Jobs\SendNewsletterJob;
use App\Models\Newsletter;
use App\Models\NewsletterSend;
use Illuminate\Console\Command;

class SendNewslettersCommand extends Command
{
    protected $signature = 'newsletters:send {--subject=} {--content=} {--period=daily}';

    protected $description = 'Queue newsletter sends for all verified subscribers.';

    public function handle(): int
    {
        $period = (string) $this->option('period');
        $subject = (string) ($this->option('subject') ?? match ($period) {
            'weekly' => 'TechNewsHub Weekly Digest',
            'monthly' => 'TechNewsHub Monthly Digest',
            default => 'TechNewsHub Daily Digest',
        });

        // Build content if not specified using NewsletterService
        $content = $this->option('content');
        if (! $content) {
            $content = app(\App\Services\NewsletterService::class)->generateDigest($period);
        }

        $count = 0;
        Newsletter::verified()->chunkById(500, function ($subscribers) use (&$count, $subject, $content) {
            foreach ($subscribers as $subscriber) {
                $send = NewsletterSend::create([
                    'subscriber_id' => $subscriber->id,
                    'batch_id' => now()->format('YmdHi'),
                    'subject' => $subject,
                    'content' => $content,
                    'status' => 'queued',
                ]);

                SendNewsletterJob::dispatch($send->id);
                $count++;
            }
        });

        $this->info("Queued {$count} newsletter sends.");

        return self::SUCCESS;
    }
}
