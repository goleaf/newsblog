<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
    public function sends(): View
    {
        $sends = NewsletterSend::latest('created_at')->paginate(50);

        return view('admin.newsletters.sends', compact('sends'));
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
