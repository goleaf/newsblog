@props(['currentUrl'])

<div 
    x-data="{
        showFilters: false,
        dateFilter: '{{ request('date_filter', '') }}',
        applyFilters() {
            const url = new URL('{{ $currentUrl }}', window.location.origin);
            const params = new URLSearchParams(window.location.search);
            
            if (this.dateFilter) {
                params.set('date_filter', this.dateFilter);
            } else {
                params.delete('date_filter');
            }
            
            // Preserve sort parameter
            const sort = params.get('sort');
            if (sort) {
                url.searchParams.set('sort', sort);
            }
            
            if (this.dateFilter) {
                url.searchParams.set('date_filter', this.dateFilter);
            }
            
            window.location.href = url.toString();
        },
        clearFilters() {
            this.dateFilter = '';
            const url = new URL('{{ $currentUrl }}', window.location.origin);
            const params = new URLSearchParams(window.location.search);
            const sort = params.get('sort');
            if (sort) {
                url.searchParams.set('sort', sort);
            }
            window.location.href = url.toString();
        }
    }"
    class="relative"
>
    <!-- Filter Button -->
    <button
        @click="showFilters = !showFilters"
        class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        :class="{ 'ring-2 ring-blue-500': dateFilter }"
    >
        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
        </svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filters</span>
        @if(request('date_filter'))
            <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-600 rounded-full">1</span>
        @endif
    </button>

    <!-- Filter Dropdown -->
    <div
        x-show="showFilters"
        @click.away="showFilters = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute left-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
        style="display: none;"
    >
        <div class="p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Filter Articles</h3>
                <button
                    @click="showFilters = false"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Date Filter -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Published Date
                </label>
                <select
                    x-model="dateFilter"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">All time</option>
                    <option value="today">Today</option>
                    <option value="week">This week</option>
                    <option value="month">This month</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                <button
                    @click="applyFilters"
                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                >
                    Apply Filters
                </button>
                <button
                    @click="clearFilters"
                    x-show="dateFilter"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors"
                >
                    Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Active Filters Display -->
    @if(request('date_filter'))
        <div class="mt-3 flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-sm">
                <span class="font-medium">Date:</span>
                <span>{{ ucfirst(request('date_filter')) }}</span>
                <button
                    @click="clearFilters"
                    class="ml-1 hover:text-blue-900 dark:hover:text-blue-200"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
        </div>
    @endif
</div>
