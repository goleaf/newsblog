@props([
    'widget',
    'categories',
    'showCount' => true,
])

<div class="bg-white dark:bg-gray-800 rounded-lg p-6">
    @if($widget->title)
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
        {{ $widget->title }}
    </h3>
    @endif
    
    @if($categories->isNotEmpty())
        <ul class="space-y-2">
            @foreach($categories as $category)
                <li>
                    <a 
                        href="{{ route('category.show', $category->slug) }}" 
                        class="flex justify-between items-center py-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors group"
                    >
                        <span class="flex items-center gap-2">
                            @if($category->icon)
                                <span class="text-lg">{{ $category->icon }}</span>
                            @endif
                            <span class="text-sm font-medium">{{ $category->name }}</span>
                        </span>
                        @if($showCount)
                            <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded group-hover:bg-blue-100 dark:group-hover:bg-blue-900 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                {{ $category->posts_count }}
                            </span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <x-ui.empty-state 
            message="No categories available"
            size="sm"
        />
    @endif
</div>
