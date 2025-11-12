@forelse($posts as $post)
    <article class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        @if($post->featured_image)
            <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-full h-48 object-cover">
        @endif
        <div class="p-4">
            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ $post->category->name }}</span>
            <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">
                <a href="{{ route('post.show', $post->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">{{ $post->title }}</a>
            </h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $post->excerpt_limited }}</p>
            <div class="mt-4 flex items-center text-xs text-gray-500 dark:text-gray-400">
                <span>{{ $post->formatted_date }}</span>
                <span class="mx-2">•</span>
                <span>{{ $post->reading_time_text }}</span>
            </div>
        </div>
    </article>
@empty
    <div class="col-span-3 text-center py-12 text-gray-500 dark:text-gray-400">No posts found.</div>
@endforelse
