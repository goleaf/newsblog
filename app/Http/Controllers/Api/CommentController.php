<?php

namespace App\Http\Controllers\Api;

use App\Enums\CommentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Services\SpamDetectionService;
use App\Support\Html\SimpleSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Comments
 *
 * API endpoints for managing article comments and replies.
 */
class CommentController extends Controller
{
    public function __construct(private SpamDetectionService $spam) {}

    public function index(Request $request, $article): JsonResponse
    {
        // Support both route parameter and query parameter for backward compatibility
        $postId = is_numeric($article) ? $article : ($request->integer('post_id') ?: null);

        if (! $postId) {
            return response()->json(['message' => 'Article ID is required'], 400);
        }

        $comments = Comment::query()
            ->approved()
            ->forPost($postId)
            ->with([
                'user:id,name,avatar',
                'parent:id,user_id,content',
                'parent.user:id,name',
            ])
            ->withCount(['replies' => function ($query) {
                $query->where('status', CommentStatus::Approved);
            }])
            ->select(['id', 'post_id', 'user_id', 'parent_id', 'content', 'status', 'created_at', 'updated_at'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => CommentResource::collection($comments->items()),
            'total' => $comments->total(),
            'links' => [
                'next' => $comments->nextPageUrl(),
                'prev' => $comments->previousPageUrl(),
            ],
        ]);
    }

    public function store(StoreCommentRequest $request, $article): JsonResponse
    {
        $validated = $request->validated();
        $validated['content'] = SimpleSanitizer::sanitize($validated['content']);

        // Use article from route parameter, fallback to request data for backward compatibility
        $postId = is_numeric($article) ? $article : ($validated['post_id'] ?? null);

        if (! $postId) {
            return response()->json(['message' => 'Article ID is required'], 400);
        }

        $context = [
            'time_on_page' => $validated['page_load_time'] ?? null,
            'honeypot' => $validated['honeypot'] ?? null,
            'ip' => $request->ip(),
        ];

        $isSpam = $this->spam->isSpam($validated['content'], $context);

        $comment = Comment::create([
            'post_id' => $postId,
            'author_name' => $validated['author_name'],
            'author_email' => $validated['author_email'],
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'status' => $isSpam ? CommentStatus::Spam : CommentStatus::Pending,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(new CommentResource($comment), 201);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $validated = $request->validated();
        $sanitized = SimpleSanitizer::sanitize($validated['content']);

        $comment->update(['content' => $sanitized]);

        return response()->json(new CommentResource($comment));
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json(null, 204);
    }
}
