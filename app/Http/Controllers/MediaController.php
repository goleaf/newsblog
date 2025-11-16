<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexMediaRequest;
use App\Http\Requests\StoreMediaRequest;
use App\Models\Media;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function __construct(public ImageProcessingService $images) {}

    public function index(IndexMediaRequest $request): JsonResponse
    {
        $query = Media::query();

        if ($q = $request->string('q')->toString()) {
            $query->where(function ($inner) use ($q): void {
                $inner->where('filename', 'like', '%'.$q.'%')
                    ->orWhere('alt_text', 'like', '%'.$q.'%')
                    ->orWhere('caption', 'like', '%'.$q.'%');
            });
        }

        if ($mime = $request->string('mime_type')->toString()) {
            $query->where('mime_type', $mime);
        }

        $perPage = (int) ($request->integer('per_page') ?: 24);

        $media = $query->latest('id')->paginate($perPage);

        return response()->json($media);
    }

    public function store(StoreMediaRequest $request): JsonResponse
    {
        $uploaded = $this->images->upload($request->file('file'));

        $absolute = Storage::path($uploaded['path']);
        $variants = $this->images->generateVariants($absolute);
        $webp = $this->images->convertToWebP($absolute);

        $metadata = array_merge($uploaded['metadata'] ?? [], [
            'variants' => $variants,
            'webp' => $webp,
        ]);

        $media = Media::query()->create([
            'filename' => $uploaded['filename'],
            'path' => $uploaded['path'],
            'mime_type' => $uploaded['mime_type'],
            'size' => $uploaded['size'],
            'alt_text' => $request->input('alt_text'),
            'caption' => $request->input('caption'),
            'metadata' => $metadata,
            'user_id' => null,
        ]);

        return response()->json($media, 201);
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        // Delete original
        if ($media->path) {
            $originalRelative = str_replace('public/', '', $media->path);
            if (Storage::disk('public')->exists($originalRelative)) {
                Storage::disk('public')->delete($originalRelative);
            }
        }

        // Delete known variants from metadata
        $variants = (array) ($media->metadata['variants'] ?? []);
        foreach ($variants as $path) {
            if (is_string($path)) {
                $rel = str_replace('public/', '', $path);
                if (Storage::disk('public')->exists($rel)) {
                    Storage::disk('public')->delete($rel);
                }
            }
        }

        // Delete webp if present
        $webp = $media->metadata['webp'] ?? null;
        if (is_string($webp)) {
            $rel = str_replace('public/', '', $webp);
            if (Storage::disk('public')->exists($rel)) {
                Storage::disk('public')->delete($rel);
            }
        }

        $media->delete();

        return response()->json(['deleted' => true]);
    }
}
