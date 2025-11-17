{{--
    Mobile Search Interface Component
    
    Full-screen search interface optimized for mobile devices.
    Requirements: 17.1, 17.2
--}}

<div 
    x-data="mobileSearch"
    x-show="open"
    x-cloak
    @keydown.escape.window="open = false"
    @mobile-search-open.window="open = true"
    class="lg:hidden fixed inset-0 z-50 bg-white dark:bg-gray-900"
    role="dialog"
    aria-modal="true"
    aria-label="Search"
>
    {{-- Search Header --}}
    <div class="sticky top-0 z-10 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
        <div class="flex items-center gap-3">
            <button 
                @click="open = false"
                type="button"
                class="p-2 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white touch-target"
                aria-label="Close search"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            
            <form 
                action="{{ route('search') }}" 
                method="GET"
                class="flex-1"
                @submit.prevent="performSearch"
            >
                <div class="relative">
                    <input 
                        type="search"
                        name="q"
                        x-model="query"
                        x-ref="searchInput"
                        placeholder="Search articles..."
                        class="w-full pl-10 pr-4 py-3 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                        style="min-height: 44px;"
                        autocomplete="off"
                        @input.debounce.300ms="searchSuggestions"
                    />
                    
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    
                    <button 
                        x-show="query.length > 0"
                        @click="query = ''; $refs.searchInput.focus()"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        aria-label="Clear search"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>
        
        {{-- Quick Filters --}}
        <div class="mt-3 flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 scrollbar-hide">
            <button 
                @click="activeFilter = 'all'"
                type="button"
                class="px-4 py-2 min-h-[44px] text-sm font-medium rounded-full whitespace-nowrap transition-colors touch-target"
                :class="activeFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
            >
                All
            </button>
            <button 
                @click="activeFilter = 'articles'"
                type="button"
                class="px-4 py-2 min-h-[44px] text-sm font-medium rounded-full whitespace-nowrap transition-colors touch-target"
                :class="activeFilter === 'articles' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
            >
                Articles
            </button>
            <button 
                @click="activeFilter = 'authors'"
                type="button"
                class="px-4 py-2 min-h-[44px] text-sm font-medium rounded-full whitespace-nowrap transition-colors touch-target"
                :class="activeFilter === 'authors' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
            >
                Authors
            </button>
            <button 
                @click="activeFilter = 'categories'"
                type="button"
                class="px-4 py-2 min-h-[44px] text-sm font-medium rounded-full whitespace-nowrap transition-colors touch-target"
                :class="activeFilter === 'categories' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
            >
                Categories
            </button>
        </div>
    </div>
    
    {{-- Search Results / Suggestions --}}
    <div class="overflow-y-auto h-full pb-20">
        {{-- Recent Searches --}}
        <div x-show="query.length === 0 && recentSearches.length > 0" class="p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Searches</h3>
                <button 
                    @click="clearRecentSearches"
                    type="button"
                    class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                >
                    Clear all
                </button>
            </div>
            
            <div class="space-y-2">
                <template x-for="search in recentSearches" :key="search">
                    <button 
                        @click="query = search; performSearch()"
                        type="button"
                        class="w-full flex items-center gap-3 p-3 text-left hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-colors touch-target"
                    >
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="flex-1 text-gray-700 dark:text-gray-300" x-text="search"></span>
                    </button>
                </template>
            </div>
        </div>
        
        {{-- Popular Searches --}}
        <div x-show="query.length === 0" class="p-4 border-t border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Popular Searches</h3>
            
            <div class="flex flex-wrap gap-2">
                <template x-for="tag in popularSearches" :key="tag">
                    <button 
                        @click="query = tag; performSearch()"
                        type="button"
                        class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors touch-target"
                        x-text="tag"
                    ></button>
                </template>
            </div>
        </div>
        
        {{-- Search Suggestions --}}
        <div x-show="query.length > 0 && suggestions.length > 0" class="p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Suggestions</h3>
            
            <div class="space-y-2">
                <template x-for="suggestion in suggestions" :key="suggestion.id">
                    <a 
                        :href="suggestion.url"
                        class="block p-3 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-colors touch-target"
                    >
                        <div class="flex items-start gap-3">
                            <img 
                                x-show="suggestion.image"
                                :src="suggestion.image"
                                :alt="suggestion.title"
                                class="w-16 h-16 rounded object-cover flex-shrink-0"
                            />
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 dark:text-white line-clamp-2" x-text="suggestion.title"></h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="suggestion.category"></p>
                            </div>
                        </div>
                    </a>
                </template>
            </div>
        </div>
        
        {{-- No Results --}}
        <div x-show="query.length > 0 && suggestions.length === 0 && !loading" class="p-8 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">No results found</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try different keywords</p>
        </div>
        
        {{-- Loading --}}
        <div x-show="loading" class="p-8 text-center">
            <div class="inline-block w-8 h-8 border-4 border-gray-200 dark:border-gray-700 border-t-blue-600 rounded-full animate-spin"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('mobileSearch', () => ({
        open: false,
        query: '',
        activeFilter: 'all',
        suggestions: [],
        recentSearches: [],
        popularSearches: ['JavaScript', 'Python', 'React', 'Laravel', 'AI', 'Web Development'],
        loading: false,
        
        init() {
            // Load recent searches from localStorage
            const saved = localStorage.getItem('recent-searches');
            if (saved) {
                this.recentSearches = JSON.parse(saved);
            }
            
            // Focus input when opened
            this.$watch('open', (value) => {
                if (value) {
                    this.$nextTick(() => {
                        this.$refs.searchInput?.focus();
                    });
                }
            });
        },
        
        async searchSuggestions() {
            if (this.query.length < 2) {
                this.suggestions = [];
                return;
            }
            
            this.loading = true;
            
            try {
                const response = await fetch(`/api/search/suggestions?q=${encodeURIComponent(this.query)}&filter=${this.activeFilter}`);
                const data = await response.json();
                this.suggestions = data.suggestions || [];
            } catch (error) {
                console.error('Search suggestions error:', error);
                this.suggestions = [];
            } finally {
                this.loading = false;
            }
        },
        
        performSearch() {
            if (this.query.trim().length === 0) return;
            
            // Save to recent searches
            this.addToRecentSearches(this.query);
            
            // Navigate to search results
            window.location.href = `/search?q=${encodeURIComponent(this.query)}&filter=${this.activeFilter}`;
        },
        
        addToRecentSearches(search) {
            // Remove if already exists
            this.recentSearches = this.recentSearches.filter(s => s !== search);
            
            // Add to beginning
            this.recentSearches.unshift(search);
            
            // Keep only last 10
            this.recentSearches = this.recentSearches.slice(0, 10);
            
            // Save to localStorage
            localStorage.setItem('recent-searches', JSON.stringify(this.recentSearches));
        },
        
        clearRecentSearches() {
            this.recentSearches = [];
            localStorage.removeItem('recent-searches');
        }
    }));
});
</script>

<style>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
