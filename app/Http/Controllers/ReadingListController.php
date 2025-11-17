<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\BookmarkCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingListController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['sharedShow']);
    }

    /**
     * Display a listing of the user's reading lists.
     */
    public function index(Request $request): View
    {
        $lists = $request->user()
            ->bookmarkCollections()
            ->withCount('bookmarks')
            ->orderBy('order')
            ->get();

        return view('reading-lists.index', compact('lists'));
    }

    /**
     * Show the form for creating a new reading list.
     */
    public function create(): View
    {
        return view('reading-lists.create');
    }

    /**
     * Store a newly created reading list.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['boolean'],
        ]);

        $order = (int) $request->user()->bookmarkCollections()->max('order') + 1;

        $collection = $request->user()->bookmarkCollections()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? false,
            'order' => $order,
        ]);

        return redirect()
            ->route('reading-lists.show', $collection)
            ->with('success', 'Reading list created successfully.');
    }

    /**
     * Display the specified reading list.
     */
    public function show(Request $request, BookmarkCollection $collection): View
    {
        // Check authorization
        if ($collection->user_id !== $request->user()->id && ! $collection->is_public) {
            abort(403, 'You do not have permission to view this reading list.');
        }

        $collection->load(['bookmarks' => function ($query) {
            $query->orderBy('order')->with(['post' => function ($q) {
                $q->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'reading_time']);
            }]);
        }]);

        $isOwner = $collection->user_id === $request->user()->id;

        return view('reading-lists.show', compact('collection', 'isOwner'));
    }

    /**
     * Show the form for editing the specified reading list.
     */
    public function edit(Request $request, BookmarkCollection $collection): View
    {
        // Check authorization
        if ($collection->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to edit this reading list.');
        }

        return view('reading-lists.edit', compact('collection'));
    }

    /**
     * Update the specified reading list.
     */
    public function update(Request $request, BookmarkCollection $collection): RedirectResponse
    {
        // Check authorization
        if ($collection->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to update this reading list.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['boolean'],
        ]);

        $collection->update($validated);

        return redirect()
            ->route('reading-lists.show', $collection)
            ->with('success', 'Reading list updated successfully.');
    }

    /**
     * Remove the specified reading list.
     */
    public function destroy(Request $request, BookmarkCollection $collection): RedirectResponse
    {
        // Check authorization
        if ($collection->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to delete this reading list.');
        }

        // Remove collection reference from bookmarks
        $collection->bookmarks()->update(['collection_id' => null]);

        $collection->delete();

        return redirect()
            ->route('reading-lists.index')
            ->with('success', 'Reading list deleted successfully.');
    }

    /**
     * Add an article to a reading list.
     */
    public function addItem(Request $request, BookmarkCollection $collection): RedirectResponse
    {
        // Check authorization
        if ($collection->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to modify this reading list.');
        }

        $validated = $request->validate([
            'post_id' => ['required', 'exists:posts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Find or create bookmark
        $bookmark = Bookmark::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'post_id' => $validated['post_id'],
            ],
            [
                'order' => 0,
            ]
        );

        // Get the max order in this collection
        $maxOrder = $collection->bookmarks()->max('order') ?? -1;

        // Update bookmark with collection and order
        $bookmark->update([
            'collection_id' => $collection->id,
            'notes' => $validated['notes'] ?? $bookmark->notes,
            'order' => $maxOrder + 1,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Article added to reading list.');
    }

    /**
     * Remove an article from a reading list.
     */
    public function removeItem(Request $request, BookmarkCollection $collection, Bookmark $bookmark): RedirectResponse
    {
        // Check authorization
        if ($collection->user_id !== $request->user()->id || $bookmark->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to modify this reading list.');
        }

        if ($bookmark->collection_id !== $collection->id) {
            abort(404, 'Bookmark not found in this reading list.');
        }

        // Remove from collection but keep the bookmark
        $bookmark->update(['collection_id' => null]);

        return redirect()
            ->back()
            ->with('success', 'Article removed from reading list.');
    }

    /**
     * Reorder articles in a reading list.
     */
    public function reorder(Request $request, BookmarkCollection $collection): RedirectResponse
    {
        // Check authorization
        if ($collection->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to modify this reading list.');
        }

        $validated = $request->validate([
            'bookmark_ids' => ['required', 'array'],
            'bookmark_ids.*' => ['integer', 'exists:bookmarks,id'],
        ]);

        // Update order for each bookmark
        foreach ($validated['bookmark_ids'] as $index => $bookmarkId) {
            Bookmark::where('id', $bookmarkId)
                ->where('user_id', $request->user()->id)
                ->where('collection_id', $collection->id)
                ->update(['order' => $index]);
        }

        return redirect()
            ->back()
            ->with('success', 'Reading list reordered successfully.');
    }

    /**
     * Generate or display share link for a reading list.
     */
    public function share(Request $request, BookmarkCollection $collection): RedirectResponse
    {
        // Check authorization
        if ($collection->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to share this reading list.');
        }

        // Generate share token if it doesn't exist
        if (! $collection->share_token) {
            $collection->update([
                'share_token' => BookmarkCollection::generateShareToken(),
                'is_public' => true,
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Share link generated successfully.')
            ->with('share_url', route('reading-lists.shared', $collection->share_token));
    }

    /**
     * Revoke share link for a reading list.
     */
    public function revokeShare(Request $request, BookmarkCollection $collection): RedirectResponse
    {
        // Check authorization
        if ($collection->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to modify this reading list.');
        }

        $collection->update(['share_token' => null]);

        return redirect()
            ->back()
            ->with('success', 'Share link revoked successfully.');
    }

    /**
     * Display a shared reading list (public access via token).
     */
    public function sharedShow(string $token): View
    {
        $collection = BookmarkCollection::where('share_token', $token)
            ->with(['bookmarks' => function ($query) {
                $query->orderBy('order')->with(['post' => function ($q) {
                    $q->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'reading_time']);
                }]);
            }, 'user:id,name'])
            ->firstOrFail();

        // Track view count (only for non-owners)
        if (! auth()->check() || auth()->id() !== $collection->user_id) {
            $collection->incrementViewCount();
        }

        return view('reading-lists.shared', compact('collection'));
    }
}
