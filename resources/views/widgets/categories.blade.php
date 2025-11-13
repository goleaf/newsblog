<div class="widget bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">{{ $widget->title }}</h3>
    
    @if($categories->count() > 0)
        <ul class="space-y-2">
            @foreach($categories as $category)
                <li>
                    <a href="{{ route('category.show', $category->slug) }}" 
                       class="flex justify-between items-center text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">
                        <span>{{ $category->name }}</span>
                        @if($showCount)
                            <span class="text-xs bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">
                                {{ $category->posts_count }}
                            </span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-gray-500 dark:text-gray-400 text-sm">No categories available.</p>
    @endif
</div>
