@extends('layouts.app')

@section('title', !empty($query) ? "Search Results for \"{$query}\"" : 'Search')

@push('styles')
<style>
    .search-highlight, mark.search-highlight {
        background-color: #fef08a;
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: 500;
    }
    .dark .search-highlight, .dark mark.search-highlight {
        background-color: #713f12;
        color: #fbbf24;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Search Header with Query and Result Count -->
    <div class="mb-6">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
            @if(!empty($query))
                Search Results
            @else
                Search
            @endif
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-400">
            @if($posts->total() > 0)
                Found <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($posts->total()) }}</span> {{ Str::plural('result', $posts->total()) }}
                @if(!empty($query))
                    for "<span class="font-semibold text-gray-900 dark:text-white">{{ e($query) }}</span>"
                @endif
            @else
                @if(!empty($query))
                    No results found for "<span class="font-semibold text-gray-900 dark:text-white">{{ e($query) }}</span>"
                @else
                    Enter a search query to find articles
                @endif
            @endif
        </p>
    </div>
    
    <!-- Search Bar with Autocomplete -->
    <div class="mb-8">
        <x-discovery.search-autocomplete 
            :placeholder="'Search articles...'"
            :show-filters="true"
        />
    </div>

    <!-- Filter Panel Component -->
    <x-discovery.filter-panel 
        :categories="$categories"
        :authors="$authors"
        :tags="$tags"
        :active-filters="$filters"
        :query="$query"
        :show-clear-all="true"
    />
    
    @if(isset($spellingSuggestion) && $spellingSuggestion)
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <span class="font-medium">Did you mean:</span>
                <a href="{{ route('search', ['q' => $spellingSuggestion]) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold">
                    {{ $spellingSuggestion }}
                </a>?
            </p>
        </div>
    @endif

    @if($posts->total() > 0)
        <!-- Sort and View Options -->
        <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                @if(isset($fuzzyEnabled) && $fuzzyEnabled)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="showRelevanceScores" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Show relevance scores</span>
                    </label>
                @endif
                @if(isset($avgRelevanceScore))
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Average match: {{ round($avgRelevanceScore) }}%
                    </span>
                @endif
            </div>
            
            <!-- Sort Dropdown -->
            <x-discovery.sort-dropdown 
                :current-sort="request('sort', 'newest')"
                :query="$query"
                :filters="$filters"
            />
        </div>
    @endif

    @if($posts->total() > 0)
        <!-- Infinite Scroll Component (Requirements 27.1-27.5) -->
        <x-infinite-scroll :posts="$posts" container-class="space-y-6">
            @foreach($posts as $post)
                @include('partials.search-post-card', ['post' => $post, 'fuzzyEnabled' => $fuzzyEnabled ?? false, 'query' => $query])
            @endforeach
        </x-infinite-scroll>
    @else
        <!-- Empty State (Requirements 2.4, 14.5) -->
        <x-ui.empty-state 
            icon="search"
            :title="!empty($query) ? 'No results found' : 'Start searching'"
            :description="!empty($query) ? 'We couldn\'t find any articles matching your search.' : 'Enter a search query to find articles'"
        >
            @if(!empty($query))
                <div class="mt-6 space-y-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p class="font-medium mb-2">Try these tips:</p>
                        <ul class="list-disc list-inside space-y-1 text-left max-w-md mx-auto">
                            <li>Check your spelling</li>
                            <li>Try different or more general keywords</li>
                            <li>Remove some filters</li>
                            <li>Use fewer words in your search</li>
                        </ul>
                    </div>
                    
                    @if($activeFilterCount > 0)
                        <a 
                            href="{{ route('search', ['q' => $query]) }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Remove all filters
                        </a>
                    @endif
                </div>
                
                <!-- Popular Articles -->
                @php
                    $popularPosts = \App\Models\Post::published()
                        ->orderByDesc('view_count')
                        ->limit(3)
                        ->get();
                @endphp
                
                @if($popularPosts->isNotEmpty())
                    <div class="mt-12">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Popular Articles
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @foreach($popularPosts as $post)
                                <x-content.post-card :post="$post" />
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </x-ui.empty-state>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('showRelevanceScores');
        const scores = document.querySelectorAll('.relevance-score');
        
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                scores.forEach(function(score) {
                    if (this.checked) {
                        score.classList.remove('hidden');
                    } else {
                        score.classList.add('hidden');
                    }
                }.bind(this));
            });
        }
    });
</script>
@endpush
@endsection

