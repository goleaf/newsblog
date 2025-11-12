<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\ImageProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageProcessingService
    ) {}

    public function index(Request $request)
    {
        $query = Media::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('file_name', 'like', "%{$search}%");
        }

        if ($request->filled('type')) {
            $query->where('file_type', $request->type);
        }

        $media = $query->recent()->paginate(24);

        return view('admin.media.index', compact('media'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('file');

        // Check if file is an image
        if ($this->imageProcessingService->isImage($file)) {
            // Process image with variants, compression, and WebP generation
            $media = $this->imageProcessingService->processUpload($file, auth()->id());
        } else {
            // Handle non-image files (documents)
            $path = $file->store('media', 'public');

            $media = Media::create([
                'user_id' => auth()->id(),
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => 'document',
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }

        return redirect()->back()->with('success', 'File uploaded successfully.');
    }

    public function destroy(Media $media)
    {
        // Use service to delete media and all variants
        if ($media->file_type === 'image') {
            $this->imageProcessingService->deleteMedia($media);
        } else {
            Storage::disk('public')->delete($media->file_path);
        }

        $media->delete();

        return redirect()->back()->with('success', 'Media deleted successfully.');
    }
}
