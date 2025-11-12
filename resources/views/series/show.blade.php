<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ $series->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="mb-6 overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 class="mb-4 text-3xl font-bold">{{ $series->name }}</h1>
                    
                    @if($series->description)
                        <p class="mb-4 text-lg text-gray-600 dark:text-gray-400">
                            {{ $series->description }}
                        </p>
                    @endif

                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ $series->posts->count() }} {{ Str::plural('post', $series->posts->count()) }}</span>
                        <span>•</span>
                        <span>Updated {{ $series->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>

            @if($series->posts->isEmpty())
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <p class="text-gray-600 dark:text-gray-400">No posts in this series yet.</p>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($series->posts as $post)
                        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                            <div class="p-6">
                                <div class="mb-4 flex items-start gap-4">
                                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-lg font-bold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                        {{ $post->pivot->order + 1 }}
                                    </span>
                                    <div class="flex-1">
                                        <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">
                                            <a href="{{ route('post.show', $post->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ $post->title }}
                                            </a>
                                        </h3>
                                        
                                        @if($post->excerpt)
                                            <p class="mb-3 text-gray-600 dark:text-gray-400">
                                                {{ $post->excerpt }}
                                            </p>
                                        @endif

                                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                            <span>by {{ $post->user->name }}</span>
                                            <span>•</span>
                                            <span>{{ $post->category->name }}</span>
                                            @if($post->published_at)
                                                <span>•</span>
                                                <span>{{ $post->published_at->format('M d, Y') }}</span>
                                            @endif
                                            @if($post->reading_time)
                                                <span>•</span>
                                                <span>{{ $post->reading_time }} min read</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <a href="{{ route('post.show', $post->slug) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        Read Post →
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
