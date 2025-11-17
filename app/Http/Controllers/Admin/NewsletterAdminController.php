<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewsletterJob;
use App\Models\Newsletter;
use App\Models\NewsletterSend;
use App\Services\NewsletterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class NewsletterAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Display newsletter dashboard.
     */
    public function index(Request $request)
    {
        // Get subscriber statistics
        $totalSubscribers = Newsletter::subscribed()->verified()->count();
        $pendingSubscribers = Newsletter::where('status', 'pending')->count();
        $unsubscribedCount = Newsletter::unsubscribed()->count();

        // Get frequency breakdown
        $frequencyBreakdown = Newsletter::subscribed()
            ->verified()
            ->selectRaw('frequency, COUNT(*) as count')
            ->groupBy('frequency')
            ->get()
            ->pluck('count', 'frequency')
            ->toArray();

        // Get recent newsletter sends
        $recentSends = NewsletterSend::with('subscriber:id,email')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->groupBy('batch_id')
            ->map(function ($sends) {
                $firstSend = $sends->first();

                return [
                    'batch_id' => $firstSend->batch_id,
                    'subject' => $firstSend->subject,
                    'total_sent' => $sends->where('status', 'sent')->count(),
                    'total_failed' => $sends->where('status', 'failed')->count(),
                    'total_opened' => $sends->whereNotNull('opened_at')->count(),
                    'total_clicked' => $sends->whereNotNull('clicked_at')->count(),
                    'created_at' => $firstSend->created_at,
                ];
            })
            ->values();

        return view('admin.newsletter.index', compact(
            'totalSubscribers',
            'pendingSubscribers',
            'unsubscribedCount',
            'frequencyBreakdown',
            'recentSends'
        ));
    }

    /**
     * Show newsletter preview.
     */
    public function preview(Request $request, NewsletterService $newsletterService)
    {
        $frequency = $request->input('frequency', 'weekly');

        // Generate newsletter content
        $content = $newsletterService->generateNewsletterContent($frequency);

        // Create a dummy subscriber for preview
        $dummySubscriber = new Newsletter([
            'email' => 'preview@example.com',
            'frequency' => $frequency,
        ]);

        $personalizedContent = $newsletterService->personalizeContent($content, $dummySubscriber);

        return view('admin.newsletter.preview', [
            'subject' => $content['subject'],
            'articles' => $content['articles'],
            'subscriber' => $dummySubscriber,
            'greeting' => $personalizedContent['greeting'],
            'frequency' => $frequency,
        ]);
    }

    /**
     * Manually trigger newsletter send.
     */
    public function send(Request $request, NewsletterService $newsletterService)
    {
        $request->validate([
            'frequency' => 'required|in:daily,weekly,monthly',
        ]);

        $frequency = $request->input('frequency');

        // Get eligible subscribers
        $subscribers = $newsletterService->getEligibleSubscribers($frequency);

        if ($subscribers->isEmpty()) {
            return back()->with('error', "No subscribers found for {$frequency} frequency.");
        }

        // Generate newsletter content
        $content = $newsletterService->generateNewsletterContent($frequency);

        if (empty($content['articles']) || $content['articles']->isEmpty()) {
            return back()->with('error', "No articles found for {$frequency} newsletter.");
        }

        // Create batch ID for tracking
        $batchId = Str::uuid()->toString();

        // Create newsletter send records and dispatch jobs
        $jobs = [];

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
        }

        // Dispatch jobs as a batch
        $batch = Bus::batch($jobs)
            ->name("Newsletter: {$frequency} - {$batchId}")
            ->allowFailures()
            ->onQueue('newsletters')
            ->dispatch();

        return back()->with('success', "Newsletter sent to {$subscribers->count()} subscribers. Batch ID: {$batchId}");
    }

    /**
     * Show performance metrics for a batch.
     */
    public function metrics(string $batchId)
    {
        $sends = NewsletterSend::where('batch_id', $batchId)->get();

        if ($sends->isEmpty()) {
            abort(404, 'Batch not found');
        }

        $totalSent = $sends->where('status', 'sent')->count();
        $totalFailed = $sends->where('status', 'failed')->count();
        $totalOpened = $sends->whereNotNull('opened_at')->count();
        $totalClicked = $sends->whereNotNull('clicked_at')->count();
        $totalClicks = $sends->sum('click_count');

        $openRate = $totalSent > 0 ? round(($totalOpened / $totalSent) * 100, 2) : 0;
        $clickRate = $totalSent > 0 ? round(($totalClicked / $totalSent) * 100, 2) : 0;
        $clickToOpenRate = $totalOpened > 0 ? round(($totalClicked / $totalOpened) * 100, 2) : 0;

        // Get most clicked links
        $allClickedLinks = $sends->pluck('clicked_links')->filter()->flatten(1);
        $linkStats = [];

        foreach ($allClickedLinks as $link) {
            $url = $link['url'] ?? null;
            if ($url) {
                if (! isset($linkStats[$url])) {
                    $linkStats[$url] = 0;
                }
                $linkStats[$url]++;
            }
        }

        arsort($linkStats);
        $topLinks = array_slice($linkStats, 0, 10, true);

        $firstSend = $sends->first();

        return view('admin.newsletter.metrics', compact(
            'batchId',
            'totalSent',
            'totalFailed',
            'totalOpened',
            'totalClicked',
            'totalClicks',
            'openRate',
            'clickRate',
            'clickToOpenRate',
            'topLinks',
            'firstSend'
        ));
    }

    /**
     * Show subscriber management.
     */
    public function subscribers(Request $request)
    {
        $query = Newsletter::query();

        // Filter by status
        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'subscribed') {
                $query->subscribed()->verified();
            } elseif ($status === 'pending') {
                $query->where('status', 'pending');
            } elseif ($status === 'unsubscribed') {
                $query->unsubscribed();
            }
        }

        // Filter by frequency
        if ($request->has('frequency')) {
            $query->where('frequency', $request->input('frequency'));
        }

        // Search by email
        if ($request->has('search')) {
            $query->where('email', 'like', '%'.$request->input('search').'%');
        }

        $subscribers = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.newsletter.subscribers', compact('subscribers'));
    }
}
