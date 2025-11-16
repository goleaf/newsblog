{{--
    Search Modal Component
    
    A full-screen search modal with autocomplete suggestions.
    Listens for 'open-search' event to open the modal.
    
    Usage:
    <x-ui.search-modal />
--}}

<div 
    x-data="{
        open: false,
        query: '',
        results: [],
        suggestions: [],
        loading: false,
        selectedIndex: -1,
        
        init() {
            // Listen for open-search event
            this.$watch('open', value => {
                if (value) {
                    this.$nextTick(() => this.$refs.searchInput.focus());
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                    this.query = '';
                    this.results = [];
                    this.suggestions = [];
                    this.selectedIndex = -1;
                }
            });
            
            window.addEventListener('open-search', () => {
                this.open = true;
            });
        },
        
        async search() {
            if (this.query.length < 2) {
                this.results = [];
                this.suggestions = [];
                return;
            }
            
            this.loading = true;
            
            try {
                const response = await fetch(`{{ route('search.suggestions') }}?q=${encodeURIComponent(this.query)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                
                const data = await response.json();
                this.suggestions = data.suggestions || [];
                this.results = data.results || [];
            } catch (error) {
                console.error('Search error:', error);
            } finally {
                this.loading = false;
            }
        },
        
        handleKeydown(event) {
            if (event.key === 'Escape') {
                this.open = false;
            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.results.length - 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
            } else if (event.key === 'Enter' && this.selectedIndex >= 0) {
                event.preventDefault();
                window.location.href = this.results[this.selectedIndex].url;
            } else if (event.key === 'Enter' && this.query.length >= 2) {
                event.preventDefault();
                window.location.href = `{{ route('search') }}?q=${encodeURIComponent(this.query)}`;
            }
        },
        
        selectSuggestion(suggestion) {
            this.query = suggestion;
            this.search();
        }
    }"
    x-show="open"
    x-cloak
    @keydown.window="handleKeydown($event)"
    class="fixed inset-0 z-50 overflow-y-auto"
    x-trap.noscroll="open"
    role="dialog"
    aria-modal="true"
    aria-labelledby="search-modal-title"
>
    {{-- Backdrop --}}
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm"
        aria-hidden="true"
    ></div>

    {{-- Modal Content --}}
    <div class="flex min-h-full items-start justify-center p-4 sm:p-6 md:p-20">
        <div 
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-3xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden"
        >
            <h2 id="search-modal-title" class="sr-only">Search</h2>
            {{-- Search Input --}}
            <div class="relative border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center px-6 py-4">
                    <svg class="w-6 h-6 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input 
                        x-ref="searchInput"
                        x-model="query"
                        @input.debounce.300ms="search()"
                        type="text"
                        placeholder="Search articles, series, topics..."
                        class="flex-1 ml-4 bg-transparent border-0 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0 text-lg"
                        id="search-input"
                        aria-label="Search"
                        role="combobox"
                        aria-autocomplete="list"
                        :aria-expanded="(results.length > 0).toString()"
                        aria-controls="search-results"
                    />
                    <button 
                        @click="open = false"
                        class="ml-4 p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                        aria-label="Close search"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Loading Indicator --}}
                <div 
                    x-show="loading"
                    class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 animate-pulse"
                ></div>
            </div>

            {{-- Search Suggestions --}}
            <div 
                x-show="suggestions.length > 0 && query.length >= 2"
                class="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"
            >
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Suggestions</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="suggestion in suggestions" :key="suggestion">
                        <button 
                            @click="selectSuggestion(suggestion)"
                            class="px-3 py-1 text-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                            x-text="suggestion"
                        ></button>
                    </template>
                </div>
            </div>

            {{-- Search Results --}}
            <div class="max-h-96 overflow-y-auto">
                <!-- Screen reader live status for result count -->
                <div class="sr-only" aria-live="polite" aria-atomic="true">
                    <span x-show="query.length >= 2 && !loading" x-text="results.length + ' results'"></span>
                </div>
                {{-- Results List --}}
                <div x-show="results.length > 0" class="py-2" id="search-results" role="listbox">
                    <template x-for="(result, index) in results" :key="result.id">
                        <a 
                            :href="result.url"
                            class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            :class="{ 'bg-gray-50 dark:bg-gray-700': selectedIndex === index }"
                            role="option"
                            :aria-selected="(selectedIndex === index).toString()"
                        >
                            <div class="flex items-start gap-4">
                                {{-- Thumbnail --}}
                                <div 
                                    x-show="result.image"
                                    class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-700"
                                >
                                    <img 
                                        :src="result.image" 
                                        :alt="result.title"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    />
                                </div>
                                
                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <h3 
                                        class="text-sm font-semibold text-gray-900 dark:text-white mb-1 line-clamp-1"
                                        x-html="result.title"
                                    ></h3>
                                    <p 
                                        class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-2"
                                        x-html="result.excerpt"
                                    ></p>
                                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-500">
                                        <span x-show="result.category" x-text="result.category"></span>
                                        <span x-show="result.reading_time">
                                            <span x-text="result.reading_time"></span> min read
                                        </span>
                                        <span x-show="result.published_at" x-text="result.published_at"></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </template>
                </div>

                {{-- Empty State --}}
                <div 
                    x-show="query.length >= 2 && results.length === 0 && !loading"
                    class="px-6 py-12 text-center"
                >
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">No results found</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Try adjusting your search terms or browse our articles
                    </p>
                </div>

                {{-- Initial State --}}
                <div 
                    x-show="query.length < 2"
                    class="px-6 py-12 text-center"
                >
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">Start typing to search</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Search for articles, series, or topics
                    </p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-4">
                        <span class="flex items-center gap-1">
                            <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs">↑</kbd>
                            <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs">↓</kbd>
                            to navigate
                        </span>
                        <span class="flex items-center gap-1">
                            <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs">Enter</kbd>
                            to select
                        </span>
                        <span class="flex items-center gap-1">
                            <kbd class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-xs">Esc</kbd>
                            to close
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
