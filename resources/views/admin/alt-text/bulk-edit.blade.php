<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Bulk Edit Alt Text') }}
            </h2>
            <a href="{{ route('admin.alt-text.report') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                ← Back to Report
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-green-800 dark:text-green-200">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($mediaItems->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                Images Without Alt Text ({{ $mediaItems->count() }})
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Add descriptive alt text to each image to improve accessibility. Alt text should describe the image content for screen reader users.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('admin.alt-text.bulk-update') }}">
                            @csrf

                            <div class="space-y-6">
                                @foreach($mediaItems as $media)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                        <div class="flex gap-4">
                                            <div class="flex-shrink-0">
                                                <img src="{{ $media->thumbnail_url }}" alt="" class="w-32 h-32 object-cover rounded">
                                            </div>

                                            <div class="flex-1">
                                                <div class="mb-2">
                                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                                                        {{ $media->file_name }}
                                                    </h4>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        Uploaded by {{ $media->user->name }} • {{ $media->created_at->format('M d, Y') }}
                                                    </p>
                                                    @if($media->title)
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                            Title: {{ $media->title }}
                                                        </p>
                                                    @endif
                                                </div>

                                                <div>
                                                    <label for="alt_text_{{ $media->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        Alt Text
                                                    </label>
                                                    <input 
                                                        type="text" 
                                                        name="alt_texts[{{ $media->id }}]" 
                                                        id="alt_text_{{ $media->id }}"
                                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                                        placeholder="Describe what's in this image..."
                                                        maxlength="255"
                                                    >
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        Example: "A developer working on a laptop with code on the screen"
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <a href="{{ route('admin.alt-text.report') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Cancel
                                </a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Update Alt Text
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-green-600 dark:text-green-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                All Images Have Alt Text!
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                All images in your media library have proper alt text.
                            </p>
                            <a href="{{ route('admin.alt-text.report') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                View Accessibility Report →
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
