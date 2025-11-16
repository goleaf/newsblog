<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostRevision;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PostRevisionService
{
    private const MAX_REVISIONS = 25;

    /**
     * Create a revision from the current post state
     */
    public function createRevision(Post $post, ?string $note = null): PostRevision
    {
        $revision = PostRevision::create([
            'post_id' => $post->id,
            'user_id' => Auth::id() ?? $post->user_id,
            'title' => $post->title,
            'content' => $post->content,
            'excerpt' => $post->excerpt,
            'meta_data' => [
                'slug' => $post->slug,
                'status' => $post->status,
                'featured_image' => $post->featured_image,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'meta_keywords' => $post->meta_keywords,
            ],
            'revision_note' => $note,
        ]);

        $this->enforceRevisionLimit($post);

        return $revision;
    }

    /**
     * Create a revision from the post's original (pre-update) attributes.
     */
    public function createRevisionFromOriginal(Post $post, ?string $note = null): PostRevision
    {
        $original = $post->getOriginal();

        $revision = PostRevision::create([
            'post_id' => $post->id,
            'user_id' => Auth::id() ?? ($original['user_id'] ?? $post->user_id),
            'title' => $original['title'] ?? $post->title,
            'content' => $original['content'] ?? $post->content,
            'excerpt' => $original['excerpt'] ?? $post->excerpt,
            'meta_data' => [
                'slug' => $original['slug'] ?? $post->slug,
                'status' => $original['status'] ?? $post->status,
                'featured_image' => $original['featured_image'] ?? $post->featured_image,
                'meta_title' => $original['meta_title'] ?? $post->meta_title,
                'meta_description' => $original['meta_description'] ?? $post->meta_description,
                'meta_keywords' => $original['meta_keywords'] ?? $post->meta_keywords,
            ],
            'revision_note' => $note,
        ]);

        $this->enforceRevisionLimit($post);

        return $revision;
    }

    /**
     * Enforce the maximum revision limit per post
     */
    private function enforceRevisionLimit(Post $post): void
    {
        $revisionCount = $post->revisions()->count();

        if ($revisionCount > self::MAX_REVISIONS) {
            $revisionsToDelete = $revisionCount - self::MAX_REVISIONS;

            $post->revisions()
                ->orderBy('created_at', 'asc')
                ->limit($revisionsToDelete)
                ->delete();
        }
    }

    /**
     * Get all revisions for a post
     */
    public function getRevisions(Post $post): Collection
    {
        return $post->revisions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get revisions for a post with pagination (Requirement 19.2)
     */
    public function getRevisionsPaginated(Post $post, int $perPage = 15): LengthAwarePaginator
    {
        return $post->revisions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Restore a post to a specific revision
     */
    public function restoreRevision(Post $post, PostRevision $revision): Post
    {
        // Create a revision of the current state before restoring
        $this->createRevision($post, 'Before restoring to revision #'.$revision->id);

        // Restore the post to the revision state
        Post::withoutEvents(function () use ($post, $revision) {
            $post->update([
                'title' => $revision->title,
                'content' => $revision->content,
                'excerpt' => $revision->excerpt,
                'slug' => $revision->meta_data['slug'] ?? $post->slug,
                'featured_image' => $revision->meta_data['featured_image'] ?? $post->featured_image,
                'meta_title' => $revision->meta_data['meta_title'] ?? null,
                'meta_description' => $revision->meta_data['meta_description'] ?? null,
                'meta_keywords' => $revision->meta_data['meta_keywords'] ?? null,
            ]);
        });

        // Create a new revision with the restored content
        $this->createRevision($post, 'Restored from revision #'.$revision->id);

        return $post->fresh();
    }

    /**
     * Compare two revisions and return differences
     */
    public function compareRevisions(PostRevision $oldRevision, PostRevision $newRevision): array
    {
        return [
            'title' => [
                'old' => $oldRevision->title,
                'new' => $newRevision->title,
                'changed' => $oldRevision->title !== $newRevision->title,
                'diff' => $this->generateDiff($oldRevision->title, $newRevision->title),
            ],
            'content' => [
                'old' => $oldRevision->content,
                'new' => $newRevision->content,
                'changed' => $oldRevision->content !== $newRevision->content,
                'diff' => $this->generateDiff($oldRevision->content, $newRevision->content),
            ],
            'excerpt' => [
                'old' => $oldRevision->excerpt,
                'new' => $newRevision->excerpt,
                'changed' => $oldRevision->excerpt !== $newRevision->excerpt,
                'diff' => $this->generateDiff($oldRevision->excerpt ?? '', $newRevision->excerpt ?? ''),
            ],
        ];
    }

    /**
     * Generate a simple diff between two strings
     */
    private function generateDiff(string $old, string $new): array
    {
        $oldLines = explode("\n", $old);
        $newLines = explode("\n", $new);

        $diff = [];
        $maxLines = max(count($oldLines), count($newLines));

        for ($i = 0; $i < $maxLines; $i++) {
            $oldLine = $oldLines[$i] ?? '';
            $newLine = $newLines[$i] ?? '';

            if ($oldLine === $newLine) {
                $diff[] = [
                    'type' => 'unchanged',
                    'content' => $oldLine,
                ];
            } elseif (empty($oldLine)) {
                $diff[] = [
                    'type' => 'added',
                    'content' => $newLine,
                ];
            } elseif (empty($newLine)) {
                $diff[] = [
                    'type' => 'removed',
                    'content' => $oldLine,
                ];
            } else {
                $diff[] = [
                    'type' => 'removed',
                    'content' => $oldLine,
                ];
                $diff[] = [
                    'type' => 'added',
                    'content' => $newLine,
                ];
            }
        }

        return $diff;
    }

    /**
     * Delete a specific revision
     */
    public function deleteRevision(PostRevision $revision): bool
    {
        return $revision->delete();
    }

    /**
     * Get a specific revision
     */
    public function getRevision(int $revisionId): ?PostRevision
    {
        return PostRevision::with(['user', 'post'])->find($revisionId);
    }
}
