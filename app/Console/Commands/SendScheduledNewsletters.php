<?php

namespace App\Console\Commands;

use App\Jobs\SendNewsletterJob;
use App\Models\NewsletterSend;
use App\Services\BatchProgressService;
use App\Services\NewsletterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendScheduledNewsletters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newsletter:send 
                            {frequency? : The frequency to send (daily, weekly, monthly)}
                            {--force : Force sending even if already sent today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled newsletters to subscribers based on their frequency preferences';

    /**
     * Execute the console command.
     */
    public function handle(NewsletterService $newsletterService, BatchProgressService $batchProgressService): int
    {
        $frequency = $this->argument('frequency');
        $force = $this->option('force');

        // Determine which frequencies to process
        $frequencies = $frequency
            ? [$frequency]
            : $this->determineFrequenciesToSend();

        if (empty($frequencies)) {
            $this->info('No newsletters scheduled to send at this time.');

            return self::SUCCESS;
        }

        $this->info('Processing newsletters for frequencies: '.implode(', ', $frequencies));

        foreach ($frequencies as $freq) {
            $this->processFrequency($freq, $newsletterService, $batchProgressService, $force);
        }

        return self::SUCCESS;
    }

    /**
     * Determine which frequencies should be sent based on current time.
     */
    protected function determineFrequenciesToSend(): array
    {
        $frequencies = [];
        $now = now();

        // Daily: Send every day at configured time (default 8 AM)
        $dailyHour = config('newsletter.daily_send_hour', 8);
        if ($now->hour === $dailyHour) {
            $frequencies[] = 'daily';
        }

        // Weekly: Send on configured day (default Monday) at configured time
        $weeklyDay = config('newsletter.weekly_send_day', 1); // 1 = Monday
        $weeklyHour = config('newsletter.weekly_send_hour', 8);
        if ($now->dayOfWeek === $weeklyDay && $now->hour === $weeklyHour) {
            $frequencies[] = 'weekly';
        }

        // Monthly: Send on configured day of month (default 1st) at configured time
        $monthlyDay = config('newsletter.monthly_send_day', 1);
        $monthlyHour = config('newsletter.monthly_send_hour', 8);
        if ($now->day === $monthlyDay && $now->hour === $monthlyHour) {
            $frequencies[] = 'monthly';
        }

        return $frequencies;
    }

    /**
     * Process newsletter sending for a specific frequency.
     */
    protected function processFrequency(string $frequency, NewsletterService $newsletterService, BatchProgressService $batchProgressService, bool $force): void
    {
        $this->info("Processing {$frequency} newsletters...");

        // Get eligible subscribers
        $subscribers = $newsletterService->getEligibleSubscribers($frequency);

        if ($subscribers->isEmpty()) {
            $this->warn("No subscribers found for {$frequency} frequency.");

            return;
        }

        $this->info("Found {$subscribers->count()} subscribers for {$frequency} frequency.");

        // Generate newsletter content
        $this->info('Generating newsletter content...');
        $content = $newsletterService->generateNewsletterContent($frequency);

        if (empty($content['articles']) || $content['articles']->isEmpty()) {
            $this->warn("No articles found for {$frequency} newsletter. Skipping.");

            return;
        }

        $this->info("Selected {$content['articles']->count()} top articles.");

        // Create batch ID for tracking
        $batchId = Str::uuid()->toString();

        // Create newsletter send records and dispatch jobs
        $jobs = [];
        $bar = $this->output->createProgressBar($subscribers->count());
        $bar->start();

        foreach ($subscribers as $subscriber) {
            // Personalize content for subscriber
            $personalizedContent = $newsletterService->personalizeContent($content, $subscriber);

            // Create newsletter send record
            $send = NewsletterSend::create([
                'subscriber_id' => $subscriber->id,
                'batch_id' => $batchId,
                'subject' => $content['subject'],
                'content' => json_encode($personalizedContent),
                'status' => 'queued',
            ]);

            // Create job for sending
            $jobs[] = new SendNewsletterJob(
                sendId: $send->id,
                subject: $content['subject'],
                articleIds: $content['articles']->pluck('id')->toArray(),
                greeting: $personalizedContent['greeting']
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Dispatch jobs as a batch
        $this->info('Dispatching newsletter jobs...');

        $batch = Bus::batch($jobs)
            ->name("Newsletter: {$frequency} - {$batchId}")
            ->allowFailures()
            ->onQueue('newsletters')
            ->then(function () use ($batchProgressService, $batchId) {
                // Batch completed successfully
                $batchProgressService->untrackBatch($batchId);
                Log::info('Newsletter batch completed', ['batch_id' => $batchId]);
            })
            ->catch(function () use ($batchId) {
                // Batch failed
                Log::error('Newsletter batch failed', ['batch_id' => $batchId]);
            })
            ->finally(function () use ($batchId) {
                // Batch finished (success or failure)
                Log::info('Newsletter batch finished', ['batch_id' => $batchId]);
            })
            ->dispatch();

        // Track the batch for progress monitoring
        $batchProgressService->trackBatch($batch->id);

        $this->info("Newsletter batch dispatched: {$batch->id}");
        $this->info("Batch ID for tracking: {$batchId}");
        $this->info('View engagement report: '.route('newsletter.report', $batchId));

        Log::info('Newsletter batch dispatched', [
            'frequency' => $frequency,
            'batch_id' => $batchId,
            'laravel_batch_id' => $batch->id,
            'subscriber_count' => $subscribers->count(),
            'article_count' => $content['articles']->count(),
        ]);
    }
}
