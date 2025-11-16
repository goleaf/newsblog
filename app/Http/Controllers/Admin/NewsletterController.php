<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
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
}
