@extends('admin.layouts.app')

@section('title', 'Media Library')

@section('content')
<div class="px-4 sm:px-0">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Media Library</h1>
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Manage your media files</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="inline">
                @csrf
                <input type="file" name="file" id="file-upload" class="hidden" onchange="this.form.submit()">
                <label for="file-upload" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 cursor-pointer">
                    Upload Media
                </label>
            </form>
        </div>
    </div>

    <!-- Media Grid -->
    <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @forelse($media as $item)
            <div class="group relative bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                @if($item->file_type === 'image')
                    <img src="{{ $item->url }}" alt="{{ $item->file_name }}" class="w-full h-32 object-cover">
                @else
                    <div class="w-full h-32 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                        <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                @endif
                <div class="p-2">
                    <p class="text-xs text-gray-600 dark:text-gray-300 truncate">{{ $item->file_name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->size_human_readable }}</p>
                </div>
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-opacity flex items-center justify-center opacity-0 group-hover:opacity-100">
                    <form method="POST" action="{{ route('admin.media.destroy', $item) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-white bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-sm" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-6 text-center py-12 text-gray-500 dark:text-gray-400">No media files found.</div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $media->links() }}
    </div>
</div>
@endsection

