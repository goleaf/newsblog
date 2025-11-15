<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\BookmarkCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkCollectionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
        ]);

        $collection = Auth::user()->bookmarkCollections()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? false,
            'order' => Auth::user()->bookmarkCollections()->max('order') + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Collection created successfully',
            'collection' => $collection,
        ]);
    }

    public function update(Request $request, BookmarkCollection $collection)
    {
        if ($collection->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
        ]);

        $collection->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Collection updated successfully',
            'collection' => $collection,
        ]);
    }

    public function destroy(BookmarkCollection $collection)
    {
        if ($collection->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Move bookmarks to no collection
        $collection->bookmarks()->update(['collection_id' => null]);

        $collection->delete();

        return response()->json([
            'success' => true,
            'message' => 'Collection deleted successfully',
        ]);
    }

    public function show(BookmarkCollection $collection)
    {
        // Check if user owns the collection or if it's public
        if ($collection->user_id !== Auth::id() && ! $collection->is_public) {
            abort(403, 'This collection is private');
        }

        $bookmarks = $collection->bookmarks()
            ->with(['post.category', 'post.user'])
            ->paginate(12);

        $categories = Auth::user()->bookmarks()
            ->with('post.category')
            ->get()
            ->pluck('post.category')
            ->unique('id')
            ->sortBy('name');

        $collections = Auth::user()->bookmarkCollections()
            ->withCount('bookmarks')
            ->get();

        return view('bookmarks.collection', compact('collection', 'bookmarks', 'categories', 'collections'));
    }

    public function moveBookmark(Request $request, Bookmark $bookmark)
    {
        if ($bookmark->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'collection_id' => 'nullable|exists:bookmark_collections,id',
        ]);

        // Verify collection belongs to user if provided
        if ($validated['collection_id']) {
            $collection = BookmarkCollection::find($validated['collection_id']);
            if ($collection->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        $bookmark->update([
            'collection_id' => $validated['collection_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bookmark moved successfully',
        ]);
    }

    public function reorder(Request $request, BookmarkCollection $collection)
    {
        if ($collection->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'bookmark_ids' => 'required|array',
            'bookmark_ids.*' => 'exists:bookmarks,id',
        ]);

        foreach ($validated['bookmark_ids'] as $index => $bookmarkId) {
            Bookmark::where('id', $bookmarkId)
                ->where('user_id', Auth::id())
                ->where('collection_id', $collection->id)
                ->update(['order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bookmarks reordered successfully',
        ]);
    }
}
