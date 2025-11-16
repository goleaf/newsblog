@props(['category'])

<a 
    href="{{ route('category.show', $category->slug) }}"
    class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors group focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset"
>
    <span class="flex items-center space-x-2">
        @if($category->icon)
            <span class="text-base" style="color: {{ $category->color_code ?? '#6B7280' }}">
                {!! $category->icon !!}
            </span>
        @endif
        <span class="group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
            {{ $category->name }}
        </span>
    </span>
    <span class="text-xs text-gray-500 dark:text-gray-400" aria-label="{{ $category->posts_count }} posts">
        {{ $category->posts_count }}
    </span>
</a>
