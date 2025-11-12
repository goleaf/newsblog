@props(['currentUrl'])

<div x-data="postFilters('{{ $currentUrl }}')" class="mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Sort Options -->
            <div class="flex items-center gap-2">
                <label for="sort" class="text-sm font-medium text-gray-700 dark:text-gray-300">Sort by:</label>
                <select 
                    id="sort" 
                    x-model="filters.sort" 
                    @change="applyFilters()"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                >
                    <option value="latest">Latest</option>
                    <option value="popular">Popular</option>
                    <option value="oldest">Oldest</option>
                </select>
            </div>

            <!-- Date Filters -->
            <div class="flex items-center gap-2">
                <label for="date_filter" class="text-sm font-medium text-gray-700 dark:text-gray-300">Date:</label>
                <select 
                    id="date_filter" 
                    x-model="filters.date_filter" 
                    @change="applyFilters()"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                >
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>

            <!-- Active Filter Count & Clear Button -->
            <div class="flex items-center gap-2">
                <span 
                    x-show="activeFilterCount > 0" 
                    x-text="`${activeFilterCount} filter${activeFilterCount > 1 ? 's' : ''} active`"
                    class="text-sm text-gray-600 dark:text-gray-400"
                ></span>
                <button 
                    x-show="activeFilterCount > 0"
                    @click="clearFilters()"
                    type="button"
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium"
                >
                    Clear all filters
                </button>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div x-show="loading" class="mt-4 text-center">
            <svg class="animate-spin h-5 w-5 text-indigo-600 dark:text-indigo-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('postFilters', (baseUrl) => ({
        filters: {
            sort: new URLSearchParams(window.location.search).get('sort') || 'latest',
            date_filter: new URLSearchParams(window.location.search).get('date_filter') || '',
        },
        loading: false,

        get activeFilterCount() {
            let count = 0;
            if (this.filters.sort !== 'latest') count++;
            if (this.filters.date_filter) count++;
            return count;
        },

        init() {
            // Listen for popstate events (browser back/forward)
            window.addEventListener('popstate', () => {
                this.loadFromUrl();
                this.fetchPosts(false); // Don't update URL on popstate
            });
        },

        loadFromUrl() {
            const params = new URLSearchParams(window.location.search);
            this.filters.sort = params.get('sort') || 'latest';
            this.filters.date_filter = params.get('date_filter') || '';
        },

        applyFilters() {
            this.fetchPosts(true);
        },

        clearFilters() {
            this.filters.sort = 'latest';
            this.filters.date_filter = '';
            this.fetchPosts(true);
        },

        async fetchPosts(updateUrl = true) {
            this.loading = true;

            // Build query string
            const params = new URLSearchParams();
            if (this.filters.sort !== 'latest') {
                params.set('sort', this.filters.sort);
            }
            if (this.filters.date_filter) {
                params.set('date_filter', this.filters.date_filter);
            }

            const queryString = params.toString();
            const url = baseUrl + (queryString ? '?' + queryString : '');

            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();

                // Update the post grid
                const postGrid = document.getElementById('post-grid');
                if (postGrid) {
                    postGrid.innerHTML = data.html;
                }

                // Update pagination
                const pagination = document.getElementById('pagination');
                if (pagination) {
                    pagination.innerHTML = data.pagination;
                }

                // Update URL without page reload (Requirement 26.5)
                if (updateUrl) {
                    const newUrl = window.location.pathname + (queryString ? '?' + queryString : '');
                    window.history.pushState({}, '', newUrl);
                }

                // Scroll to top of results
                postGrid?.scrollIntoView({ behavior: 'smooth', block: 'start' });

            } catch (error) {
                console.error('Error fetching posts:', error);
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>
