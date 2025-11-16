<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyMediaRequest;
use App\Http\Requests\IndexMediaRequest;
use App\Http\Requests\SearchMediaRequest;
use App\Http\Requests\StoreMediaRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $mediaService,
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

        $media = $this->mediaService->upload(
            file: $file,
            userId: $validated['user_id'] ?? $request->user()?->id,
            altText: $validated['alt_text'] ?? null,
            caption: $validated['caption'] ?? null
        );

        return response()->json([
            'data' => $media,
            'message' => __('Media uploaded successfully.'),
        ], 201);
    }

    /**
     * Delete a media item and its files.
     */
    public function destroy(DestroyMediaRequest $request, Media $media): JsonResponse
    {
        $this->mediaService->delete($media);

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
