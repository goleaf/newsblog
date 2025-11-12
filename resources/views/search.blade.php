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
    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Search Results for "{{ e($query) }}"</h1>
    
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

    @if(isset($fuzzyEnabled) && $fuzzyEnabled && isset($avgRelevanceScore))
        <div class="mb-6 flex items-center gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="showRelevanceScores" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-sm text-gray-600 dark:text-gray-400">Show relevance scores</span>
            </label>
        </div>
    @endif

    <div class="space-y-6">
        @forelse($posts as $post)
            <article class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="md:flex">
                    @if($post->featured_image)
                        <div class="md:flex-shrink-0">
                            <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="h-48 w-full md:w-48 object-cover">
                        </div>
                    @endif
                    <div class="p-6 flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ $post->category->name }}</span>
                            @if(isset($post->relevance_score) && isset($fuzzyEnabled) && $fuzzyEnabled)
                                <span class="relevance-score hidden text-xs text-gray-500 dark:text-gray-400">
                                    ({{ round($post->relevance_score) }}% match)
                                </span>
                            @endif
                        </div>
                        <h3 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">
                            <a href="{{ route('post.show', $post->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                {!! $post->highlighted_title ?? e($post->title) !!}
                            </a>
                        </h3>
                        <p class="mt-2 text-gray-500 dark:text-gray-400">
                            {!! $post->highlighted_excerpt ?? e($post->excerpt_limited ?? '') !!}
                        </p>
                        <div class="mt-4 flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <span>{{ $post->formatted_date }}</span>
                            <span class="mx-2">•</span>
                            <span>{{ $post->reading_time_text }}</span>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <p class="text-lg mb-2">No posts found matching your search.</p>
                <p class="text-sm">Try different keywords or check your spelling.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($posts->hasPages())
        <div class="mt-8">
            {{ $posts->links() }}
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

