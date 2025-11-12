<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostRevision;
use App\Services\PostRevisionService;
use Illuminate\Http\Request;

class PostRevisionController extends Controller
{
    public function __construct(
        private PostRevisionService $revisionService
    ) {}

    /**
     * Display revision history for a post
     */
    public function index(Post $post)
    {
        $this->authorize('update', $post);

        $revisions = $this->revisionService->getRevisions($post);

        return view('admin.posts.revisions.index', compact('post', 'revisions'));
    }

    /**
     * Show a specific revision
     */
    public function show(Post $post, PostRevision $revision)
    {
        $this->authorize('update', $post);

        if ($revision->post_id !== $post->id) {
            abort(404);
        }

        return view('admin.posts.revisions.show', compact('post', 'revision'));
    }

    /**
     * Compare two revisions
     */
    public function compare(Post $post, Request $request)
    {
        $this->authorize('update', $post);

        $request->validate([
            'old_revision_id' => 'required|exists:post_revisions,id',
            'new_revision_id' => 'required|exists:post_revisions,id',
        ]);

        $oldRevision = $this->revisionService->getRevision($request->old_revision_id);
        $newRevision = $this->revisionService->getRevision($request->new_revision_id);

        if ($oldRevision->post_id !== $post->id || $newRevision->post_id !== $post->id) {
            abort(404);
        }

        $diff = $this->revisionService->compareRevisions($oldRevision, $newRevision);

        return view('admin.posts.revisions.compare', compact('post', 'oldRevision', 'newRevision', 'diff'));
    }

    /**
     * Restore a specific revision
     */
    public function restore(Post $post, PostRevision $revision)
    {
        $this->authorize('update', $post);

        if ($revision->post_id !== $post->id) {
            abort(404);
        }

        $this->revisionService->restoreRevision($post, $revision);

        return redirect()
            ->route('admin.posts.revisions.index', $post)
            ->with('success', 'Post restored to revision #'.$revision->id);
    }

    /**
     * Delete a specific revision
     */
    public function destroy(Post $post, PostRevision $revision)
    {
        $this->authorize('update', $post);

        if ($revision->post_id !== $post->id) {
            abort(404);
        }

        $this->revisionService->deleteRevision($revision);

        return redirect()
            ->route('admin.posts.revisions.index', $post)
            ->with('success', 'Revision deleted successfully');
    }
}
