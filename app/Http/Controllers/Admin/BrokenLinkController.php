<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrokenLink;
use Illuminate\Http\Request;

class BrokenLinkController extends Controller
{
    public function index()
    {
        $brokenLinks = BrokenLink::with('post')
            ->pending()
            ->orderBy('last_checked_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_pending' => BrokenLink::pending()->count(),
            'total_fixed' => BrokenLink::fixed()->count(),
            'total_ignored' => BrokenLink::ignored()->count(),
        ];

        return view('admin.broken-links.index', compact('brokenLinks', 'stats'));
    }

    public function markAsFixed(BrokenLink $brokenLink)
    {
        $brokenLink->markAsFixed();

        return back()->with('success', 'Link marked as fixed.');
    }

    public function markAsIgnored(BrokenLink $brokenLink)
    {
        $brokenLink->markAsIgnored();

        return back()->with('success', 'Link marked as ignored.');
    }

    public function destroy(BrokenLink $brokenLink)
    {
        $brokenLink->delete();

        return back()->with('success', 'Broken link removed.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:fix,ignore,delete',
            'ids' => 'required|array',
            'ids.*' => 'exists:broken_links,id',
        ]);

        $brokenLinks = BrokenLink::whereIn('id', $request->ids)->get();

        foreach ($brokenLinks as $brokenLink) {
            match ($request->action) {
                'fix' => $brokenLink->markAsFixed(),
                'ignore' => $brokenLink->markAsIgnored(),
                'delete' => $brokenLink->delete(),
            };
        }

        $message = match ($request->action) {
            'fix' => 'Links marked as fixed.',
            'ignore' => 'Links marked as ignored.',
            'delete' => 'Links removed.',
        };

        return back()->with('success', $message);
    }
}
