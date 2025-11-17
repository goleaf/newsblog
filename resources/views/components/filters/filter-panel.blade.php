@props([
    'categories' => [],
    'authors' => [],
    'tags' => [],
    'sortOptions' => [],
    'currentFilters' => [],
    'currentSort' => 'newest',
    'actionUrl' => null
])

<div 
    x-data="{
        showFilters: false,
        filters: {
            category: '{{ $currentFilters['category'] ?? '' }}',
            author: '{{ $currentFilters['author'] ?? '' }}',
            tags: {{ json_encode($currentFilters['tags'] ?? []) }},
            date_from: '{{ $currentFilters['date_from'] ?? '' }}',
            date_to: '{{ $currentFilters['date_to'] ?? '' }}',
            reading_time_min: {{ $currentFilters['reading_time_min'] ?? 0 }},
            reading_time_max: {{ $currentFilters['reading_time_max'] ?? 60 }},
            sort: '{{ $currentSort }}'
        },
        applyFilters() {
            const url = new URL('{{ $actionUrl ?? url()->current() }}', window.location.origin);
            
            // Add all non-empty filters
            if (this.filters.category) url.searchParams.set('category', this.filters.category);
            if (this.filters.author) url.searchParams.set('author', this.filters.author);
            if (this.filters.tags && this.filters.tags.length > 0) {
                this.filters.tags.forEach(tag => url.searchParams.append('tags[]', tag));
            }
            if (this.filters.date_from) url.searchParams.set('date_from', this.filters.date_from);
            if (this.filters.date_to) url.searchParams.set('date_to', this.filters.date_to);
            if (this.filters.reading_time_min > 0) url.searchParams.set('reading_time_min', this.filters.reading_time_min);
            if (this.filters.reading_time_max < 60) url.searchParams.set('reading_time_max', this.filters.reading_time_max);
            if (this.filters.sort) url.searchParams.set('sort', this.filters.sort);
            
            // Preserve query parameter if exists
            const query = new URLSearchParams(window.location.search).get('q');
            if (query) url.searchParams.set('q', query);
            
            window.location.href = url.toString();
        },
        clearFilters() {
            this.filters = {
                category: '',
                author: '',
                tags: [],
                date_from: '',
                date_to: '',
                reading_time_min: 0,
                reading_time_max: 60,
                sort: 'newest'
            };
            
            const url = new URL('{{ $actionUrl ?? url()->current() }}', window.location.origin);
            const query = new URLSearchParams(window.location.search).get('q');
            if (query) url.searchParams.set('q', query);
            
            window.location.href = url.toString();
        },
        getActiveFilterCount() {
            let count = 0;
            if (this.filters.category) count++;
            if (this.filters.author) count++;
            if (this.filters.tags && this.filters.tags.length > 0) count++;
            if (this.filters.date_from || this.filters.date_to) count++;
            if (this.filters.reading_time_min > 0 || this.filters.reading_time_max < 60) count++;
            return count;
        }
    }"
    class="relative"
>
    <!-- Filter and Sort Controls -->
    <div class="flex items-center gap-3 mb-4">
        <!-- Filter Button -->
        <button
            type="button"
            @click="showFilters = !showFilters"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            :class="{ 'ring-2 ring-blue-500': getActiveFilterCount() > 0 }"
        >
            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filters</span>
            <span x-show="getActiveFilterCount() > 0" class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-600 rounded-full" x-text="getActiveFilterCount()"></span>
        </button>

        <!-- Sort Dropdown -->
        <x-filters.sort-dropdown 
            :sortOptions="$sortOptions" 
            :currentSort="$currentSort" 
        />
    </div>

    <!-- Filter Panel -->
    <div
        x-show="showFilters"
        @click.away="showFilters = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2"
        class="mb-6 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700"
        style="display: none;"
    >
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Filter Articles</h3>
            <button
                type="button"
                @click="showFilters = false"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Category Filter -->
            @if(count($categories) > 0)
                <x-filters.category-dropdown 
                    :categories="$categories" 
                    :selected="$currentFilters['category'] ?? null" 
                />
            @endif

            <!-- Author Filter -->
            @if(count($authors) > 0)
                <x-filters.author-dropdown 
                    :authors="$authors" 
                    :selected="$currentFilters['author'] ?? null" 
                />
            @endif

            <!-- Tag Filter -->
            @if(count($tags) > 0)
                <x-filters.tag-multiselect 
                    :tags="$tags" 
                    :selected="$currentFilters['tags'] ?? []" 
                />
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <!-- Date Range Filter -->
            <x-filters.date-range-picker 
                :dateFrom="$currentFilters['date_from'] ?? null" 
                :dateTo="$currentFilters['date_to'] ?? null" 
            />

            <!-- Reading Time Filter -->
            <x-filters.reading-time-slider 
                :minTime="$currentFilters['reading_time_min'] ?? null" 
                :maxTime="$currentFilters['reading_time_max'] ?? null" 
            />
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <button
                type="button"
                @click="applyFilters"
                class="flex-1 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
            >
                Apply Filters
            </button>
            <button
                type="button"
                @click="clearFilters"
                x-show="getActiveFilterCount() > 0"
                class="px-6 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors"
            >
                Clear All
            </button>
        </div>
    </div>

    <!-- Active Filters Display -->
    <div x-show="getActiveFilterCount() > 0" class="mb-4 flex flex-wrap gap-2">
        <!-- Category Badge -->
        <span x-show="filters.category" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-sm">
            <span class="font-medium">Category:</span>
            <span x-text="$el.closest('[x-data]').querySelector('[name=category] option:checked')?.textContent || filters.category"></span>
            <button
                type="button"
                @click="filters.category = ''; applyFilters()"
                class="ml-1 hover:text-blue-900 dark:hover:text-blue-200"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </span>

        <!-- Author Badge -->
        <span x-show="filters.author" class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-sm">
            <span class="font-medium">Author:</span>
            <span x-text="$el.closest('[x-data]').querySelector('[name=author] option:checked')?.textContent || filters.author"></span>
            <button
                type="button"
                @click="filters.author = ''; applyFilters()"
                class="ml-1 hover:text-green-900 dark:hover:text-green-200"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </span>

        <!-- Tags Badge -->
        <span x-show="filters.tags && filters.tags.length > 0" class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-full text-sm">
            <span class="font-medium">Tags:</span>
            <span x-text="filters.tags.length + ' selected'"></span>
            <button
                type="button"
                @click="filters.tags = []; applyFilters()"
                class="ml-1 hover:text-purple-900 dark:hover:text-purple-200"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </span>

        <!-- Date Range Badge -->
        <span x-show="filters.date_from || filters.date_to" class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-full text-sm">
            <span class="font-medium">Date:</span>
            <span x-text="(filters.date_from || 'Any') + ' - ' + (filters.date_to || 'Any')"></span>
            <button
                type="button"
                @click="filters.date_from = ''; filters.date_to = ''; applyFilters()"
                class="ml-1 hover:text-yellow-900 dark:hover:text-yellow-200"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </span>

        <!-- Reading Time Badge -->
        <span x-show="filters.reading_time_min > 0 || filters.reading_time_max < 60" class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-full text-sm">
            <span class="font-medium">Reading Time:</span>
            <span x-text="filters.reading_time_min + '-' + filters.reading_time_max + ' min'"></span>
            <button
                type="button"
                @click="filters.reading_time_min = 0; filters.reading_time_max = 60; applyFilters()"
                class="ml-1 hover:text-red-900 dark:hover:text-red-200"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </span>
    </div>
</div>
