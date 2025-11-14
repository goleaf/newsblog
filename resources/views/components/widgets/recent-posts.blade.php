@props([
    'widget',
    'posts',
])

<div class="bg-white dark:bg-gray-800 rounded-lg p-6">
    @if($widget->title)
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
        {{ $widget->title }}
    </h3>
    @endif
    
    @if($posts->isNotEmpty())
        <ul class="space-y-4">
            @foreach($posts as $post)
                <li>
                    <a href="{{ route('post.show', $post->slug) }}" class="flex gap-3 group">
                        @if($post->featured_image)
                            <div class="flex-shrink-0">
                                <img 
                                    src="{{ $post->featured_image_url }}" 
                                    alt="{{ $post->image_alt_text ?? $post->title }}"
                                    class="w-16 h-16 object-cover rounded"
                                    loading="lazy"
                                >
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 line-clamp-2 transition-colors">
                                {{ $post->title }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <time datetime="{{ $post->published_at->toIso8601String() }}">
                                    {{ $post->published_at->diffForHumans() }}
                                </time>
                            </p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <x-ui.empty-state 
            message="No recent posts available"
            size="sm"
        />
    @endif
</div>
