<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEditorsPicksOrderRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EditorsPicksController extends Controller
{
    public function index(): View
    {
        // Only published posts are allowed for editor's picks
        $picks = Post::published()
            ->where('is_editors_pick', true)
            ->orderByRaw('COALESCE(editors_pick_order, 9999) asc')
            ->select(['id', 'title', 'slug', 'editors_pick_order', 'featured_image', 'image_alt_text'])
            ->take(50)
            ->get();

        return view('editors-picks.index', compact('picks'));
    }

    public function updateOrder(UpdateEditorsPicksOrderRequest $request): RedirectResponse
    {
        $orderedIds = $request->validated('order'); // array<int>

        // Reset orders for all current picks (and keep only published)
        Post::where('is_editors_pick', true)->update(['editors_pick_order' => null]);

        foreach ($orderedIds as $index => $postId) {
            Post::published()
                ->where('id', $postId)
                ->update([
                    'is_editors_pick' => true,
                    'editors_pick_order' => $index + 1,
                ]);
        }

        return redirect()->route('editors-picks.index')->with('success', __('Editor\'s picks updated'));
    }
}


