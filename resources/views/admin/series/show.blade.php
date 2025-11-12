<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Series') }}: {{ $series->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.series.edit', $series) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                    Edit Series
                </a>
                <a href="{{ route('admin.series.index') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                    Back to Series
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold">Description</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            {{ $series->description ?: 'No description provided.' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Slug</p>
                            <p class="font-medium">{{ $series->slug }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Posts</p>
                            <p class="font-medium">{{ $series->posts->count() }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Created</p>
                            <p class="font-medium">{{ $series->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="mb-4 text-lg font-semibold">Posts in Series</h3>
                    
                    @if($series->posts->isEmpty())
                        <p class="text-gray-600 dark:text-gray-400">No posts in this series yet.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($series->posts as $post)
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="flex items-start justify-between">
                                        <div class="flex gap-4">
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                                {{ $post->pivot->order + 1 }}
                                            </span>
                                            <div>
                                                <h4 class="font-medium">
                                                    <a href="{{ route('post.show', $post->slug) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400" target="_blank">
                                                        {{ $post->title }}
                                                    </a>
                                                </h4>
                                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                    by {{ $post->user->name }} • {{ $post->category->name }} • {{ $post->published_at?->format('M d, Y') }}
                                                </p>
                                                @if($post->excerpt)
                                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                        {{ Str::limit($post->excerpt, 150) }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">
                                            {{ $post->status }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
