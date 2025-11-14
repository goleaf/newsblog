@props([
    'placeholder' => 'Search articles...',
    'mobile' => false,
    'showFilters' => false,
    'autofocus' => false,
])

<div 
    x-data="{ 
        query: '{{ request('q', '') }}',
        focused: false
    }"
    class="relative w-full"
    role="search"
>
    <form 
        method="GET" 
        action="{{ route('search') }}"
        class="relative"
    >
        {{-- Search Icon --}}
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg 
                class="h-5 w-5 text-gray-400 dark:text-gray-500" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        {{-- Search Input --}}
        <input 
            type="search"
            name="q"
            x-model="query"
            @focus="focused = true"
            @blur="focused = false"
            placeholder="{{ $placeholder }}"
            {{ $autofocus ? 'autofocus' : '' }}
            class="block w-full pl-10 pr-12 py-2.5 
                   {{ $mobile ? 'text-base' : 'text-sm' }}
                   border border-gray-300 dark:border-gray-600 
                   rounded-lg 
                   bg-white dark:bg-gray-800 
                   text-gray-900 dark:text-gray-100 
                   placeholder-gray-500 dark:placeholder-gray-400
                   focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 
                   focus:border-transparent
                   transition-all duration-200"
            :class="{ 'ring-2 ring-blue-500 dark:ring-blue-400': focused }"
            aria-label="Search articles"
            aria-describedby="search-description"
        />

        {{-- Screen Reader Description --}}
        <span id="search-description" class="sr-only">
            Search for articles by title, content, author, or tags
        </span>

        {{-- Clear Button --}}
        <button 
            type="button"
            x-show="query.length > 0"
            @click="query = ''; $el.previousElementSibling.focus()"
            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
            aria-label="Clear search"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Submit Button (Hidden, form submits on Enter) --}}
        <button type="submit" class="sr-only">Search</button>
    </form>

    {{-- Filter Button (Optional) --}}
    @if($showFilters)
        <button 
            @click="$dispatch('toggle-filters')"
            class="absolute inset-y-0 right-10 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
            aria-label="Toggle search filters"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
        </button>
    @endif

    {{-- Search Hints (Desktop Only) --}}
    @if(!$mobile)
        <div 
            x-show="focused && query.length === 0"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-4 z-50"
            @click.away="focused = false"
        >
            <div class="space-y-3">
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        Search Tips
                    </h4>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Search by title, content, or author</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Use quotes for exact phrases</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Typos are automatically corrected</span>
                        </li>
                    </ul>
                </div>

                {{-- Popular Searches --}}
                @php
                    $popularSearches = ['Laravel', 'Vue.js', 'React', 'PHP', 'JavaScript'];
                @endphp
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        Popular Searches
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($popularSearches as $search)
                            <a 
                                href="{{ route('search', ['q' => $search]) }}"
                                class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-full transition-colors"
                            >
                                {{ $search }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
