@props(['series', 'navigation'])

<div class="rounded-lg border border-indigo-200 bg-indigo-50 p-6 dark:border-indigo-800 dark:bg-indigo-900/20">
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Part of Series: 
            <a href="{{ route('series.show', $series->slug) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                {{ $series->name }}
            </a>
        </h3>
        
        @if($series->description)
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ Str::limit($series->description, 100) }}
            </p>
        @endif
    </div>

    <div class="mb-4">
        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
            <span>Part {{ $navigation['current_position'] }} of {{ $navigation['total_posts'] }}</span>
            <a href="{{ route('series.show', $series->slug) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                View all posts
            </a>
        </div>
        
        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
            <div 
                class="h-full bg-indigo-600 transition-all duration-300"
                style="width: {{ ($navigation['current_position'] / $navigation['total_posts']) * 100 }}%"
            ></div>
        </div>
    </div>

    <div class="flex items-center justify-between gap-4">
        @if($navigation['previous'])
            <a href="{{ route('post.show', $navigation['previous']->slug) }}" class="flex-1 rounded-md border border-gray-300 bg-white px-4 py-3 text-left transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">← Previous</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                    {{ Str::limit($navigation['previous']->title, 40) }}
                </div>
            </a>
        @else
            <div class="flex-1"></div>
        @endif

        @if($navigation['next'])
            <a href="{{ route('post.show', $navigation['next']->slug) }}" class="flex-1 rounded-md border border-gray-300 bg-white px-4 py-3 text-right transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">Next →</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                    {{ Str::limit($navigation['next']->title, 40) }}
                </div>
            </a>
        @else
            <div class="flex-1"></div>
        @endif
    </div>
</div>
