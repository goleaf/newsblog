<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $collection->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-200">
                    @if($collection->description)
                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ $collection->description }}</p>
                    @endif
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @forelse($collection->bookmarks as $bookmark)
                            @php($post = $bookmark->post)
                            <a href="{{ route('post.show', $post->slug) }}" class="block group">
                                <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 group-hover:border-indigo-300 dark:group-hover:border-indigo-500 transition-colors">
                                    @if($post->featured_image)
                                        <img src="{{ $post->featured_image_url }}" alt="{{ $post->image_alt_text ?? $post->title }}" class="w-full h-40 object-cover" />
                                    @endif
                                    <div class="p-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $post->title }}</h3>
                                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($post->excerpt, 120) }}</p>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <p class="text-sm text-gray-600 dark:text-gray-400">This reading list is empty.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

