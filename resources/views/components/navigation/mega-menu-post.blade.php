@props(['post'])

<a 
    href="{{ route('post.show', $post->slug) }}"
    class="flex gap-3 group focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset rounded-md"
>
    @if($post->featured_image)
        <img 
            src="{{ $post->featured_image }}" 
            alt="{{ $post->title }}"
            class="w-16 h-16 object-cover rounded-md flex-shrink-0"
            loading="lazy"
        />
    @endif
    <div class="flex-1 min-w-0">
        <h5 class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2 mb-1">
            {{ $post->title }}
        </h5>
        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <time datetime="{{ $post->published_at->toIso8601String() }}">
                {{ $post->published_at->diffForHumans() }}
            </time>
            <span aria-hidden="true">•</span>
            <span>{{ $post->reading_time }} min read</span>
        </div>
    </div>
</a>
