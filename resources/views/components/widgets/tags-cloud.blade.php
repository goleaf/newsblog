@props(['widget', 'tags'])

@php
	$max = max($tags->pluck('posts_count')->all() ?: [1]);
@endphp

<div class="widget-box">
	<h3 class="text-base font-semibold text-gray-800 dark:text-gray-100 mb-3">{{ $widget->title }}</h3>
	<div class="flex flex-wrap gap-2">
		@foreach ($tags as $tag)
			@php
				$ratio = $max ? ($tag->posts_count / $max) : 0;
				$size = 0.85 + ($ratio * 0.65); // 0.85rem - 1.5rem
			@endphp
			<a href="{{ route('tag.show', $tag->slug) }}"
			   class="inline-block text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-200"
			   style="font-size: {{ number_format($size, 2) }}rem">
				#{{ $tag->name }}
			</a>
		@endforeach
	</div>
</div>

@props([
    'widget',
    'tags',
])

<div class="bg-white dark:bg-gray-800 rounded-lg p-6">
    @if($widget->title)
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
        {{ $widget->title }}
    </h3>
    @endif
    
    @if($tags->isNotEmpty())
        @php
            $maxCount = $tags->max('posts_count');
            $minCount = $tags->min('posts_count');
        @endphp
        
        <div class="flex flex-wrap gap-2">
            @foreach($tags as $tag)
                @php
                    // Calculate font size based on post count (from text-xs to text-lg)
                    $ratio = $maxCount > $minCount 
                        ? ($tag->posts_count - $minCount) / ($maxCount - $minCount)
                        : 0.5;
                    
                    $sizeClasses = [
                        'text-xs',
                        'text-sm',
                        'text-base',
                        'text-lg',
                    ];
                    
                    $sizeIndex = (int) floor($ratio * (count($sizeClasses) - 1));
                    $sizeClass = $sizeClasses[$sizeIndex];
                @endphp
                
                <a 
                    href="{{ route('tag.show', $tag->slug) }}" 
                    class="{{ $sizeClass }} inline-flex items-center gap-1 px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-blue-900 hover:text-blue-600 dark:hover:text-blue-400 rounded-full transition-colors"
                    title="{{ $tag->posts_count }} {{ Str::plural('post', $tag->posts_count) }}"
                >
                    <span>#{{ $tag->name }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $tag->posts_count }}
                    </span>
                </a>
            @endforeach
        </div>
    @else
        <x-ui.empty-state 
            message="No tags available"
            size="sm"
        />
    @endif
</div>
