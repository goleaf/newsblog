@props([
    'category',
    'active' => false,
    'showCount' => true,
])

<a 
    href="{{ route('category.show', $category->slug) }}"
    {{ $attributes->merge([
        'class' => 'flex items-center space-x-2 px-4 py-2 rounded-full text-sm font-medium transition-colors whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 ' . 
                   ($active 
                       ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' 
                       : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700')
    ]) }}
    style="border-left: 3px solid {{ $category->color_code ?? '#6B7280' }}"
>
    @if($category->icon)
        <span class="text-base" style="color: {{ $category->color_code ?? '#6B7280' }}">
            {!! $category->icon !!}
        </span>
    @endif
    <span>{{ $category->name }}</span>
    @if($showCount)
        <span class="text-xs text-gray-500 dark:text-gray-400" aria-label="{{ $category->posts_count }} posts">
            ({{ $category->posts_count }})
        </span>
    @endif
</a>
