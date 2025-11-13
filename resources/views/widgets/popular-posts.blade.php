<div class="widget bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">{{ $widget->title }}</h3>
    
    @if($posts->count() > 0)
        <ul class="space-y-3">
            @foreach($posts as $post)
                <li>
                    <a href="{{ route('post.show', $post->slug) }}" class="flex gap-3 group">
                        @if($post->featured_image)
                            <img src="{{ asset('storage/' . $post->featured_image) }}" 
                                 alt="{{ $post->title }}"
                                 class="w-16 h-16 object-cover rounded">
                        @endif
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 line-clamp-2">
                                {{ $post->title }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ number_format($post->view_count) }} views
                            </p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-gray-500 dark:text-gray-400 text-sm">No popular posts available.</p>
    @endif
</div>
