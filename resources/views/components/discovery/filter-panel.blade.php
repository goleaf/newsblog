@props([
    'categories' => collect(),
    'authors' => collect(),
    'tags' => collect(),
    'activeFilters' => [],
    'query' => '',
    'showClearAll' => true,
])

<div 
    x-data="{ 
        showFilters: {{ !empty($activeFilters) && count(array_filter($activeFilters)) > 0 ? 'true' : 'false' }},
        activeFilterCount: {{ count(array_filter($activeFilters)) }}
    }"
    class="mb-6"
>
    <!-- Filter Toggle Button -->
    <div class="flex items-center justify-between mb-4">
        <button 
            @click="showFilters = !showFilters"
            type="button"
            class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            aria-expanded="false"
            :aria-expanded="showFilters.toString()"
        >
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filters</span>
            <template x-if="activeFilterCount > 0">
                <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-indigo-600 rounded-full">
                    <span x-text="activeFilterCount"></span>
                </span>
            </template>
        </button>

        @if($showClearAll && !empty($activeFilters) && count(array_filter($activeFilters)) > 0)
            <a 
                href="{{ route('search', ['q' => $query]) }}"
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
            >
                Clear all filters
            </a>
        @endif
    </div>

    <!-- Active Filter Badges -->
    @if(!empty($activeFilters) && count(array_filter($activeFilters)) > 0)
        <div class="flex flex-wrap gap-2 mb-4">
            @if(!empty($activeFilters['date_from']) || !empty($activeFilters['date_to']))
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Date: 
                    @if(!empty($activeFilters['date_from']))
                        {{ date('M j, Y', strtotime($activeFilters['date_from'])) }}
                    @endif
                    @if(!empty($activeFilters['date_from']) && !empty($activeFilters['date_to']))
                        -
                    @endif
                    @if(!empty($activeFilters['date_to']))
                        {{ date('M j, Y', strtotime($activeFilters['date_to'])) }}
                    @endif
                    <a href="{{ route('search', array_merge(request()->except(['date_from', 'date_to']), ['q' => $query])) }}" class="ml-1 hover:text-indigo-900 dark:hover:text-indigo-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </span>
            @endif
            
            @if(!empty($activeFilters['author']))
                @php
                    $author = $authors->firstWhere('id', $activeFilters['author']);
                @endphp
                @if($author)
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Author: {{ $author->name }}
                        <a href="{{ route('search', array_merge(request()->except('author'), ['q' => $query])) }}" class="ml-1 hover:text-indigo-900 dark:hover:text-indigo-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </a>
                    </span>
                @endif
            @endif
            
            @if(!empty($activeFilters['category']))
                @php
                    $category = $categories->firstWhere('id', $activeFilters['category']);
                @endphp
                @if($category)
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Category: {{ $category->name }}
                        <a href="{{ route('search', array_merge(request()->except('category'), ['q' => $query])) }}" class="ml-1 hover:text-indigo-900 dark:hover:text-indigo-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </a>
                    </span>
                @endif
            @endif
            
            @if(!empty($activeFilters['tags']) && is_array($activeFilters['tags']))
                @foreach($activeFilters['tags'] as $tagId)
                    @php
                        $tag = $tags->firstWhere('id', $tagId);
                    @endphp
                    @if($tag)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm">
                            #{{ $tag->name }}
                            <a href="{{ route('search', array_merge(request()->all(), ['tags' => array_diff($activeFilters['tags'], [$tagId]), 'q' => $query])) }}" class="ml-1 hover:text-indigo-900 dark:hover:text-indigo-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </a>
                        </span>
                    @endif
                @endforeach
            @endif
        </div>
    @endif

    <!-- Filter Panel -->
    <div 
        x-show="showFilters" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2"
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-sm"
    >
        <form method="GET" action="{{ route('search') }}" class="space-y-6">
            <input type="hidden" name="q" value="{{ $query }}">

            <!-- Date Range Filter -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Date Range</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            From
                        </label>
                        <input 
                            type="date" 
                            id="date_from" 
                            name="date_from" 
                            value="{{ $activeFilters['date_from'] ?? '' }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            To
                        </label>
                        <input 
                            type="date" 
                            id="date_to" 
                            name="date_to" 
                            value="{{ $activeFilters['date_to'] ?? '' }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>
                </div>
            </div>

            <!-- Author and Category Filters -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Author Filter -->
                <div>
                    <label for="author" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                        Author
                    </label>
                    <select 
                        id="author" 
                        name="author"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Authors</option>
                        @foreach($authors as $author)
                            <option 
                                value="{{ $author->id }}" 
                                {{ ($activeFilters['author'] ?? '') == $author->id ? 'selected' : '' }}
                            >
                                {{ $author->name }} ({{ $author->posts_count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Category Filter -->
                <div>
                    <label for="category" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                        Category
                    </label>
                    <select 
                        id="category" 
                        name="category"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option 
                                value="{{ $category->id }}" 
                                {{ ($activeFilters['category'] ?? '') == $category->id ? 'selected' : '' }}
                            >
                                {{ $category->parent_id ? '— ' : '' }}{{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Tags Multi-Select -->
            @if($tags->isNotEmpty())
                <div>
                    <h3 class="block text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        Tags
                    </h3>
                    <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto p-2 bg-gray-50 dark:bg-gray-900 rounded-md">
                        @foreach($tags as $tag)
                            <label class="inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="tags[]" 
                                    value="{{ $tag->id }}"
                                    {{ in_array($tag->id, $activeFilters['tags'] ?? []) ? 'checked' : '' }}
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $tag->name }} <span class="text-gray-500 dark:text-gray-400">({{ $tag->posts_count }})</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Filter Actions -->
            <div class="flex items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button 
                    type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium shadow-sm"
                >
                    Apply Filters
                </button>
                <a 
                    href="{{ route('search', ['q' => $query]) }}"
                    class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-medium"
                >
                    Reset
                </a>
                <button 
                    type="button"
                    @click="showFilters = false"
                    class="ml-auto text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                >
                    Close
                </button>
            </div>
        </form>
    </div>
</div>
