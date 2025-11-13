<div class="widget bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">{{ $widget->title }}</h3>
    
    @if($tags->count() > 0)
        <div class="flex flex-wrap gap-2">
            @foreach($tags as $tag)
                @php
                    $size = match(true) {
                        $tag->posts_count > 10 => 'text-lg',
                        $tag->posts_count > 5 => 'text-base',
                        default => 'text-sm'
                    };
                @endphp
                <a href="{{ route('tag.show', $tag->slug) }}" 
                   class="{{ $size }} bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full hover:bg-blue-200 dark:hover:bg-blue-800">
                    {{ $tag->name }}
                </a>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 dark:text-gray-400 text-sm">No tags available.</p>
    @endif
</div>
