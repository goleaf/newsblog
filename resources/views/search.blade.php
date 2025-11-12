@extends('layouts.app')

@section('title', 'Search Results')

@push('styles')
<style>
    .search-highlight {
        background-color: #fef08a;
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: 500;
    }
    .dark .search-highlight {
        background-color: #713f12;
        color: #fbbf24;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Search Results</h1>
        <p class="text-lg text-gray-600 dark:text-gray-400">
            @if($posts->total() > 0)
                Found {{ $posts->total() }} {{ Str::plural('result', $posts->total()) }}
                @if(!empty($query))
                    for "<span class="font-semibold">{{ e($query) }}</span>"
                @endif
            @else
                No results found
                @if(!empty($query))
                    for "<span class="font-semibold">{{ e($query) }}</span>"
                @endif
            @endif
        </p>
    </div>

    <!-- Advanced Filters -->
    <div x-data="{ showFilters: {{ !empty($filters) && $activeFilterCount > 0 ? 'true' : 'false' }} }" class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <button 
                @click="showFilters = !showFilters"
                class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filters</span>
                @if($activeFilterCount > 0)
                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-indigo-600 rounded-full">
                        {{ $activeFilterCount }}
                    </span>
                @endif
            </button>

            @if($activeFilterCount > 0)
                <a 
                    href="{{ route('search', ['q' => $query]) }}"
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                    Clear all filters
                </a>
            @endif
        </div>

        <div x-show="showFilters" x-transition class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <form method="GET" action="{{ route('search') }}" class="space-y-4">
                <input type="hidden" name="q" value="{{ $query }}">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Date From -->
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Date From
                        </label>
                        <input 
                            type="date" 
                            id="date_from" 
                            name="date_from" 
                            value="{{ $filters['date_from'] ?? '' }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <!-- Date To -->
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Date To
                        </label>
                        <input 
                            type="date" 
                            id="date_to" 
                            name="date_to" 
                            value="{{ $filters['date_to'] ?? '' }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <!-- Author -->
                    <div>
                        <label for="author" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
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
                                    {{ ($filters['author'] ?? '') == $author->id ? 'selected' : '' }}
                                >
                                    {{ $author->name }} ({{ $author->posts_count }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
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
                                    {{ ($filters['category'] ?? '') == $category->id ? 'selected' : '' }}
                                >
                                    {{ $category->parent_id ? '— ' : '' }}{{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Tags Multi-Select -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Tags
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <label class="inline-flex items-center">
                                <input 
                                    type="checkbox" 
                                    name="tags[]" 
                                    value="{{ $tag->id }}"
                                    {{ in_array($tag->id, $filters['tags'] ?? []) ? 'checked' : '' }}
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $tag->name }} ({{ $tag->posts_count }})
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium"
                    >
                        Apply Filters
                    </button>
                    <a 
                        href="{{ route('search', ['q' => $query]) }}"
                        class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
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

    @if(isset($fuzzyEnabled) && $fuzzyEnabled && $posts->total() > 0)
        <div class="mb-6 flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="showRelevanceScores" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-sm text-gray-600 dark:text-gray-400">Show relevance scores</span>
            </label>
            @if(isset($avgRelevanceScore))
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Average match: {{ round($avgRelevanceScore) }}%
                </span>
            @endif
        </div>
    @endif

    @if($posts->total() > 0)
        <!-- Infinite Scroll Component (Requirements 27.1-27.5) -->
        <x-infinite-scroll :posts="$posts" container-class="space-y-6">
            @foreach($posts as $post)
                @include('partials.search-post-card', ['post' => $post, 'fuzzyEnabled' => $fuzzyEnabled ?? false])
            @endforeach
        </x-infinite-scroll>
    @else
        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
            <p class="text-lg mb-2">No posts found matching your search.</p>
            <p class="text-sm">Try different keywords or check your spelling.</p>
        </div>
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

