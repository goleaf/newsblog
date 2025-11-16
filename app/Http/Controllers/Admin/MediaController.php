<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyMediaRequest;
use App\Http\Requests\IndexMediaRequest;
use App\Http\Requests\SearchMediaRequest;
use App\Http\Requests\StoreMediaRequest;
use App\Models\Media;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    public function __construct(
        protected ImageProcessingService $imageProcessingService,
    ) {}

    /**
     * Display a paginated media library listing with optional search.
     */
    public function index(IndexMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Media::query()->images()->orderByDesc('created_at');

        if (! empty($validated['q'] ?? null)) {
            $search = $validated['q'];
            $query->where(function ($q) use ($search) {
                $q->where('file_name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('caption', 'like', "%{$search}%");
            });
        }

        if (! empty($validated['mime_type'] ?? null)) {
            $query->where('mime_type', $validated['mime_type']);
        }

        $perPage = $validated['per_page'] ?? 24;
        $media = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $media->items(),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    /**
     * Store a newly uploaded media item.
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $validated['file'];

        $media = $this->imageProcessingService->upload($file, (int) $validated['user_id']);

        // Optionally update alt_text and caption if provided
        if (! empty($validated['alt_text'] ?? null) || ! empty($validated['caption'] ?? null)) {
            $media->update([
                'alt_text' => $validated['alt_text'] ?? $media->alt_text,
                'caption' => $validated['caption'] ?? $media->caption,
            ]);
        }

        return response()->json([
            'data' => $media->fresh(),
            'message' => __('Media uploaded successfully.'),
        ], 201);
    }

    /**
     * Delete a media item and its files.
     */
    public function destroy(DestroyMediaRequest $request, Media $media): JsonResponse
    {
        $this->imageProcessingService->deleteMedia($media);
        $media->delete();

        return response()->json([
            'message' => __('Media deleted successfully.'),
        ]);
    }

    /**
     * Search media items.
     */
    public function search(SearchMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Media::query()->images()->orderByDesc('created_at');

        $search = $validated['q'];

        $query->where(function ($q) use ($search) {
            $q->where('file_name', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhere('caption', 'like', "%{$search}%");
        });

        if (! empty($validated['mime_type'] ?? null)) {
            $query->where('mime_type', $validated['mime_type']);
        }

        $media = $query->limit(50)->get();

        return response()->json([
            'data' => $media,
        ]);
    }
}
