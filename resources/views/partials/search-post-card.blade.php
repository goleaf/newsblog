@props(['post', 'fuzzyEnabled' => false])

<article data-post-item class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <div class="md:flex">
        @if($post->featured_image)
            <div class="md:flex-shrink-0">
                <img 
                    src="{{ $post->featured_image_url }}" 
                    alt="{{ $post->title }}" 
                    class="h-48 w-full md:w-48 object-cover"
                    loading="lazy"
                >
            </div>
        @endif
        <div class="p-6 flex-1">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ $post->category->name }}</span>
                @if(isset($post->relevance_score) && $fuzzyEnabled)
                    <span class="relevance-score hidden text-xs px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full font-medium">
                        {{ round($post->relevance_score) }}% match
                    </span>
                @endif
                @if(isset($post->match_type))
                    <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full">
                        {{ ucfirst($post->match_type) }} match
                    </span>
                @endif
            </div>
            <h3 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">
                <a href="{{ route('post.show', $post->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                    {!! $post->highlighted_title ?? e($post->title) !!}
                </a>
            </h3>
            <p class="mt-2 text-gray-500 dark:text-gray-400">
                {!! $post->highlighted_excerpt ?? e($post->excerpt_limited ?? '') !!}
            </p>
            <div class="mt-4 flex items-center text-sm text-gray-500 dark:text-gray-400">
                <span>{{ $post->formatted_date }}</span>
                <span class="mx-2">•</span>
                <span>{{ $post->reading_time_text }}</span>
            </div>
        </div>
    </div>
</article>
