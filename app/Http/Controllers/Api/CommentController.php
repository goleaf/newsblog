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

class CommentController extends Controller
{
    public function __construct(private SpamDetectionService $spam) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate(['post_id' => ['required', 'integer', 'exists:posts,id']]);

        $comments = Comment::query()
            ->approved()
            ->forPost($request->integer('post_id'))
            ->withCount('replies')
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

    public function store(StoreCommentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['content'] = SimpleSanitizer::sanitize($validated['content']);

        $context = [
            'time_on_page' => $validated['page_load_time'] ?? null,
            'honeypot' => $validated['honeypot'] ?? null,
            'ip' => $request->ip(),
        ];

        $isSpam = $this->spam->isSpam($validated['content'], $context);

        $comment = Comment::create([
            'post_id' => $validated['post_id'],
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
