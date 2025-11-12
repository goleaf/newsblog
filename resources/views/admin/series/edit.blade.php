<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Edit Series') }}: {{ $series->name }}
            </h2>
            <a href="{{ route('admin.series.index') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                Back to Series
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 dark:bg-green-900">
                    <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
                </div>
            @endif

            <div class="mb-6 overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="mb-4 text-lg font-semibold">Series Details</h3>
                    <form method="POST" action="{{ route('admin.series.update', $series) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $series->name) }}"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Slug
                            </label>
                            <input
                                type="text"
                                name="slug"
                                id="slug"
                                value="{{ old('slug', $series->slug) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                            @error('slug')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Description
                            </label>
                            <textarea
                                name="description"
                                id="description"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >{{ old('description', $series->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end">
                            <button
                                type="submit"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                            >
                                Update Series
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mb-6 overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="mb-4 text-lg font-semibold">Posts in Series</h3>
                    
                    @if($series->posts->isEmpty())
                        <p class="mb-4 text-gray-600 dark:text-gray-400">No posts in this series yet.</p>
                    @else
                        <div class="mb-4 space-y-2" id="posts-list">
                            @foreach($series->posts as $post)
                                <div class="flex items-center justify-between rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-post-id="{{ $post->id }}">
                                    <div class="flex items-center gap-4">
                                        <span class="text-gray-500 dark:text-gray-400">#{{ $post->pivot->order + 1 }}</span>
                                        <div>
                                            <h4 class="font-medium">{{ $post->title }}</h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                by {{ $post->user->name }} in {{ $post->category->name }}
                                            </p>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.series.posts.remove', [$series, $post]) }}" class="inline" onsubmit="return confirm('Remove this post from the series?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
                        <h4 class="mb-4 font-medium">Add Post to Series</h4>
                        <form method="POST" action="{{ route('admin.series.posts.add', $series) }}">
                            @csrf
                            <div class="flex gap-4">
                                <select
                                    name="post_id"
                                    required
                                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="">Select a post...</option>
                                    @foreach($availablePosts as $post)
                                        <option value="{{ $post->id }}">{{ $post->title }}</option>
                                    @endforeach
                                </select>
                                <button
                                    type="submit"
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                                >
                                    Add Post
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
