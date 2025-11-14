@props([
    'placeholder' => 'Search articles...',
    'showFilters' => false,
    'minLength' => 3,
])

<div 
    x-data="searchAutocomplete"
    x-init="init()"
    @click.away="closeResults()"
    class="relative w-full"
>
    <!-- Search Input -->
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        
        <input 
            type="search"
            x-model="query"
            @input.debounce.300ms="search()"
            @keydown="handleKeydown($event)"
            @focus="showDropdown = true"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            class="block w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            aria-label="Search articles"
            aria-autocomplete="list"
            aria-controls="search-results"
            :aria-expanded="showDropdown && (results.length > 0 || recentSearches.length > 0 || popularSearches.length > 0)"
        >
        
        <!-- Clear Button -->
        <button 
            x-show="query.length > 0"
            @click="clearSearch()"
            type="button"
            class="absolute inset-y-0 right-0 pr-3 flex items-center"
            aria-label="Clear search"
        >
            <svg class="h-5 w-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        
        <!-- Loading Indicator -->
        <div 
            x-show="loading"
            class="absolute inset-y-0 right-10 pr-3 flex items-center pointer-events-none"
        >
            <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>
    
    <!-- Dropdown Results -->
    <div 
        x-show="showDropdown && (results.length > 0 || recentSearches.length > 0 || popularSearches.length > 0 || query.length >= {{ $minLength }})"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        id="search-results"
        role="listbox"
        class="absolute z-50 mt-2 w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto"
    >
        <!-- Search Suggestions -->
        <template x-if="results.length > 0">
            <div class="py-2">
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Suggestions
                </div>
                <template x-for="(result, index) in results" :key="index">
                    <a 
                        :href="`{{ route('search') }}?q=${encodeURIComponent(result)}`"
                        @click="selectResult(result)"
                        @mouseenter="selectedIndex = index"
                        :class="{ 'bg-gray-100 dark:bg-gray-700': selectedIndex === index }"
                        class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition"
                        role="option"
                        :aria-selected="selectedIndex === index"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <span class="text-sm text-gray-900 dark:text-white" x-html="highlightMatch(result, query)"></span>
                        </div>
                    </a>
                </template>
            </div>
        </template>
        
        <!-- Recent Searches -->
        <template x-if="recentSearches.length > 0 && query.length === 0">
            <div class="py-2 border-t border-gray-200 dark:border-gray-700">
                <div class="px-4 py-2 flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Recent Searches
                    </span>
                    <button 
                        @click="clearRecentSearches()"
                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                    >
                        Clear
                    </button>
                </div>
                <template x-for="(search, index) in recentSearches" :key="'recent-' + index">
                    <a 
                        :href="`{{ route('search') }}?q=${encodeURIComponent(search)}`"
                        @click="selectResult(search)"
                        @mouseenter="selectedIndex = results.length + index"
                        :class="{ 'bg-gray-100 dark:bg-gray-700': selectedIndex === results.length + index }"
                        class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition"
                        role="option"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm text-gray-900 dark:text-white" x-text="search"></span>
                        </div>
                    </a>
                </template>
            </div>
        </template>
        
        <!-- Popular Searches -->
        <template x-if="popularSearches.length > 0 && query.length === 0 && recentSearches.length === 0">
            <div class="py-2">
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Popular Searches
                </div>
                <template x-for="(search, index) in popularSearches" :key="'popular-' + index">
                    <a 
                        :href="`{{ route('search') }}?q=${encodeURIComponent(search)}`"
                        @click="selectResult(search)"
                        @mouseenter="selectedIndex = index"
                        :class="{ 'bg-gray-100 dark:bg-gray-700': selectedIndex === index }"
                        class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition"
                        role="option"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            <span class="text-sm text-gray-900 dark:text-white" x-text="search"></span>
                        </div>
                    </a>
                </template>
            </div>
        </template>
        
        <!-- No Results -->
        <template x-if="query.length >= {{ $minLength }} && results.length === 0 && !loading">
            <div class="px-4 py-6 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    No suggestions found
                </p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    Press Enter to search anyway
                </p>
            </div>
        </template>
        
        <!-- Search Tip -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                <span class="font-medium">Tip:</span> Use arrow keys to navigate, Enter to select, Esc to close
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('searchAutocomplete', () => ({
        query: '',
        results: [],
        recentSearches: [],
        popularSearches: [],
        loading: false,
        showDropdown: false,
        selectedIndex: -1,
        minLength: {{ $minLength }},
        
        init() {
            // Load recent searches from localStorage
            const stored = localStorage.getItem('recentSearches');
            if (stored) {
                try {
                    this.recentSearches = JSON.parse(stored);
                } catch (e) {
                    this.recentSearches = [];
                }
            }
            
            // Load popular searches (could be from API in production)
            this.popularSearches = [
                'Laravel',
                'Vue.js',
                'React',
                'TypeScript',
                'Tailwind CSS'
            ];
        },
        
        async search() {
            if (this.query.length < this.minLength) {
                this.results = [];
                this.showDropdown = true;
                return;
            }
            
            this.loading = true;
            this.showDropdown = true;
            
            try {
                const response = await fetch(`{{ route('search.suggestions') }}?q=${encodeURIComponent(this.query)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.results = Array.isArray(data) ? data : [];
                    this.selectedIndex = -1;
                } else {
                    this.results = [];
                }
            } catch (error) {
                console.error('Search error:', error);
                this.results = [];
            } finally {
                this.loading = false;
            }
        },
        
        handleKeydown(event) {
            const totalItems = this.results.length + 
                (this.query.length === 0 ? this.recentSearches.length : 0);
            
            switch(event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.selectedIndex = Math.min(this.selectedIndex + 1, totalItems - 1);
                    this.scrollToSelected();
                    break;
                    
                case 'ArrowUp':
                    event.preventDefault();
                    this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                    this.scrollToSelected();
                    break;
                    
                case 'Enter':
                    event.preventDefault();
                    if (this.selectedIndex >= 0 && this.selectedIndex < this.results.length) {
                        this.selectResult(this.results[this.selectedIndex]);
                    } else if (this.selectedIndex >= this.results.length && this.query.length === 0) {
                        const recentIndex = this.selectedIndex - this.results.length;
                        if (recentIndex < this.recentSearches.length) {
                            this.selectResult(this.recentSearches[recentIndex]);
                        }
                    } else if (this.query.length > 0) {
                        this.selectResult(this.query);
                    }
                    break;
                    
                case 'Escape':
                    this.closeResults();
                    break;
            }
        },
        
        selectResult(result) {
            this.saveRecentSearch(result);
            window.location.href = `{{ route('search') }}?q=${encodeURIComponent(result)}`;
        },
        
        saveRecentSearch(query) {
            // Remove if already exists
            this.recentSearches = this.recentSearches.filter(s => s !== query);
            
            // Add to beginning
            this.recentSearches.unshift(query);
            
            // Keep only last 5
            this.recentSearches = this.recentSearches.slice(0, 5);
            
            // Save to localStorage
            localStorage.setItem('recentSearches', JSON.stringify(this.recentSearches));
        },
        
        clearRecentSearches() {
            this.recentSearches = [];
            localStorage.removeItem('recentSearches');
        },
        
        clearSearch() {
            this.query = '';
            this.results = [];
            this.selectedIndex = -1;
            this.showDropdown = true;
        },
        
        closeResults() {
            this.showDropdown = false;
            this.selectedIndex = -1;
        },
        
        scrollToSelected() {
            this.$nextTick(() => {
                const container = document.getElementById('search-results');
                const selected = container?.querySelector('[aria-selected="true"]');
                if (selected && container) {
                    const containerRect = container.getBoundingClientRect();
                    const selectedRect = selected.getBoundingClientRect();
                    
                    if (selectedRect.bottom > containerRect.bottom) {
                        container.scrollTop += selectedRect.bottom - containerRect.bottom;
                    } else if (selectedRect.top < containerRect.top) {
                        container.scrollTop -= containerRect.top - selectedRect.top;
                    }
                }
            });
        },
        
        highlightMatch(text, query) {
            if (!query) return this.escapeHtml(text);
            
            const escapedText = this.escapeHtml(text);
            const escapedQuery = this.escapeHtml(query);
            const regex = new RegExp(`(${escapedQuery})`, 'gi');
            
            return escapedText.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-900 font-medium">$1</mark>');
        },
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }));
});
</script>
@endpush
