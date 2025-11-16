<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsletterSendsFilterRequest;
use App\Models\Newsletter;
use App\Models\NewsletterSend;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function index(): View
    {
        $newsletters = Newsletter::latest()->paginate(50);

        $stats = Cache::remember('admin:newsletter:stats', 300, function () {
            return [
                'total' => Newsletter::count(),
                'verified' => Newsletter::verified()->count(),
                'pending' => Newsletter::whereNull('verified_at')->where('status', 'pending')->count(),
                'unsubscribed' => Newsletter::unsubscribed()->count(),
            ];
        });

        return view('admin.newsletters.index', compact('newsletters', 'stats'));
    }

    public function export(): Response
    {
        $subscribers = Newsletter::verified()
            ->where('status', 'subscribed')
            ->orderBy('verified_at', 'desc')
            ->get();

        $csv = "Email,Verified At,Subscribed At\n";

        foreach ($subscribers as $subscriber) {
            $csv .= sprintf(
                "%s,%s,%s\n",
                $subscriber->email,
                $subscriber->verified_at?->format('Y-m-d H:i:s') ?? '',
                $subscriber->created_at->format('Y-m-d H:i:s')
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="newsletter-subscribers-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    /**
     * List recent newsletter sends.
     */
    public function sends(NewsletterSendsFilterRequest $request): View
    {
        $query = NewsletterSend::query();

        // Filters: batch_id, status, date range
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->string('batch_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to'));
        }

        $sends = $query->latest('created_at')->paginate(50)->withQueryString();

        // Aggregated metrics (lightweight, cached briefly)
        $summary = Cache::remember('admin:newsletter:sends:summary', 300, function () {
            $totals = [
                'total' => NewsletterSend::count(),
                'sent' => NewsletterSend::where('status', 'sent')->count(),
                'queued' => NewsletterSend::where('status', 'queued')->count(),
                'failed' => NewsletterSend::where('status', 'failed')->count(),
            ];

            // Sum opens/clicks for the latest N sends (avoid scanning all rows)
            $recentIds = NewsletterSend::latest('id')->limit(500)->pluck('id');
            $opens = 0;
            $clicks = 0;
            foreach ($recentIds as $id) {
                $opens += (int) Cache::get("newsletter:send:{$id}:opens", 0);
                $clicks += (int) Cache::get("newsletter:send:{$id}:clicks", 0);
            }

            $ctr = $totals['sent'] > 0 ? round(($clicks / $totals['sent']) * 100, 2) : 0.0;

            return [
                'total' => $totals['total'],
                'sent' => $totals['sent'],
                'queued' => $totals['queued'],
                'failed' => $totals['failed'],
                'opens' => $opens,
                'clicks' => $clicks,
                'ctr' => $ctr,
            ];
        });

        // Batch summaries: latest 10 batches with status counts and recent opens/clicks
        $batches = Cache::remember('admin:newsletter:sends:batches', 300, function () {
            $latestBatchIds = NewsletterSend::whereNotNull('batch_id')
                ->select('batch_id')
                ->distinct()
                ->latest('batch_id')
                ->limit(10)
                ->pluck('batch_id');

            $batchSummaries = [];

            foreach ($latestBatchIds as $batchId) {
                $query = NewsletterSend::where('batch_id', $batchId);
                $total = (clone $query)->count();
                $sent = (clone $query)->where('status', 'sent')->count();
                $queued = (clone $query)->where('status', 'queued')->count();
                $failed = (clone $query)->where('status', 'failed')->count();

                // Approximate opens/clicks across sends in this batch (recent only via cache)
                $ids = (clone $query)->select('id')->limit(1000)->pluck('id');
                $opens = 0;
                $clicks = 0;
                foreach ($ids as $id) {
                    $opens += (int) Cache::get("newsletter:send:{$id}:opens", 0);
                    $clicks += (int) Cache::get("newsletter:send:{$id}:clicks", 0);
                }
                $ctr = $sent > 0 ? round(($clicks / $sent) * 100, 2) : 0.0;

                $batchSummaries[] = [
                    'batch_id' => $batchId,
                    'total' => $total,
                    'sent' => $sent,
                    'queued' => $queued,
                    'failed' => $failed,
                    'opens' => $opens,
                    'clicks' => $clicks,
                    'ctr' => $ctr,
                ];
            }

            return $batchSummaries;
        });

        return view('admin.newsletters.sends', [
            'sends' => $sends,
            'summary' => $summary,
            'batches' => $batches,
            'filters' => [
                'batch_id' => (string) $request->string('batch_id'),
                'status' => (string) $request->string('status'),
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ],
        ]);
    }

    /**
     * Export batch summaries as CSV (optionally filtered by date or batch).
     */
    public function exportSends(NewsletterSendsFilterRequest $request): Response
    {
        $csv = "batch_id,total,sent,queued,failed,opens,clicks,ctr\n";

        $batchQuery = NewsletterSend::query()->whereNotNull('batch_id');
        if ($request->filled('batch_id')) {
            $batchQuery->where('batch_id', $request->string('batch_id'));
        }
        if ($request->filled('from')) {
            $batchQuery->where('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $batchQuery->where('created_at', '<=', $request->date('to'));
        }

        $batchIds = $batchQuery->select('batch_id')->distinct()->pluck('batch_id');

        foreach ($batchIds as $batchId) {
            $query = NewsletterSend::where('batch_id', $batchId);
            $total = (clone $query)->count();
            $sent = (clone $query)->where('status', 'sent')->count();
            $queued = (clone $query)->where('status', 'queued')->count();
            $failed = (clone $query)->where('status', 'failed')->count();

            $ids = (clone $query)->select('id')->limit(2000)->pluck('id');
            $opens = 0;
            $clicks = 0;
            foreach ($ids as $id) {
                $opens += (int) Cache::get("newsletter:send:{$id}:opens", 0);
                $clicks += (int) Cache::get("newsletter:send:{$id}:clicks", 0);
            }
            $ctr = $sent > 0 ? round(($clicks / $sent) * 100, 2) : 0.0;

            $csv .= sprintf(
                "%s,%d,%d,%d,%d,%d,%d,%0.2f\n",
                $batchId,
                $total,
                $sent,
                $queued,
                $failed,
                $opens,
                $clicks,
                $ctr,
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="newsletter-batches-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Show a specific newsletter send with metrics and content preview.
     */
    public function showSend(NewsletterSend $send): View
    {
        $metrics = [
            'opens' => (int) Cache::get("newsletter:send:{$send->id}:opens", 0),
            'clicks' => (int) Cache::get("newsletter:send:{$send->id}:clicks", 0),
            'last_opened_at' => Cache::get("newsletter:send:{$send->id}:last_opened_at"),
            'last_clicked_at' => Cache::get("newsletter:send:{$send->id}:last_clicked_at"),
        ];

        $subscriber = Newsletter::find($send->subscriber_id);

        return view('admin.newsletters.send', compact('send', 'metrics', 'subscriber'));
    }

    /**
     * Requeue a specific send for delivery.
     */
    public function resend(NewsletterSend $send)
    {
        $send->update(['status' => 'queued', 'sent_at' => null]);
        \App\Jobs\SendNewsletterJob::dispatch($send->id);

        return redirect()->route('admin.newsletters.sends.show', $send)
            ->with('success', 'Send requeued for delivery.');
    }
}
