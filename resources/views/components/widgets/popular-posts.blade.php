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
                            <div class="flex items-center gap-2 mt-1">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($post->view_count) }} views
                                </p>
                            </div>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <x-ui.empty-state 
            message="No popular posts available"
            size="sm"
        />
    @endif
</div>
