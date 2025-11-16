<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexMediaRequest;
use App\Http\Requests\StoreMediaRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    /**
     * List user's uploaded media with optional filtering.
     */
    public function index(IndexMediaRequest $request): JsonResponse
    {
        $query = Media::query()->images();

        // Filter by search query
        if ($q = $request->string('q')->toString()) {
            $query->where(function ($inner) use ($q): void {
                $inner->where('file_name', 'like', '%'.$q.'%')
                    ->orWhere('alt_text', 'like', '%'.$q.'%')
                    ->orWhere('caption', 'like', '%'.$q.'%');
            });
        }

        // Filter by mime type
        if ($mime = $request->string('mime_type')->toString()) {
            $query->where('mime_type', $mime);
        }

        // Filter by user if authenticated
        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        }

        $perPage = (int) ($request->integer('per_page') ?: 24);
        $media = $query->latest('id')->paginate($perPage);

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
     * Upload a new image.
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $media = $this->mediaService->upload(
            file: $request->file('file'),
            userId: $request->user()?->id,
            altText: $request->input('alt_text'),
            caption: $request->input('caption')
        );

        return response()->json([
            'data' => $media,
            'message' => __('Media uploaded successfully.'),
        ], 201);
    }

    /**
     * Delete an uploaded image and its variants.
     */
    public function destroy(Request $request, Media $media): JsonResponse
    {
        // Authorization: only the owner or admin can delete
        if ($request->user() && $media->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json([
                'message' => __('Unauthorized to delete this media.'),
            ], 403);
        }

        $this->mediaService->delete($media);

        return response()->json([
            'message' => __('Media deleted successfully.'),
        ]);
    }
}
