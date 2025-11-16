<?php

namespace App\Console\Commands;

use App\Jobs\SendNewsletterJob;
use App\Models\Newsletter;
use App\Models\NewsletterSend;
use Illuminate\Console\Command;

class SendNewslettersCommand extends Command
{
    protected $signature = 'newsletters:send {--subject=} {--content=}';

    protected $description = 'Queue newsletter sends for all verified subscribers.';

    public function handle(): int
    {
        $subject = (string) ($this->option('subject') ?? 'TechNewsHub Newsletter');
        $content = (string) ($this->option('content') ?? '<p>Your latest news digest.</p>');

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
