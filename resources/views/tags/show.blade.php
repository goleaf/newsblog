@extends('layouts.app')

@section('title', '#' . $tag->name . ' - Tagged Articles')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumbs -->
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
            <li>
                <a href="{{ route('home') }}" class="hover:text-gray-700 dark:hover:text-gray-300">
                    Home
                </a>
            </li>
            <li>
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
            </li>
            <li>
                <span class="text-gray-400 dark:text-gray-500">Tags</span>
            </li>
            <li>
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
            </li>
            <li class="text-gray-900 dark:text-white font-medium" aria-current="page">
                #{{ $tag->name }}
            </li>
        </ol>
    </nav>

    <!-- Tag Header (Requirement 5.3) -->
    <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-shrink-0 w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white">
                    #{{ $tag->name }}
                </h1>
            </div>
        </div>
        
        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                {{ $posts->total() }} {{ Str::plural('article', $posts->total()) }}
            </span>
        </div>
    </div>

    <!-- Related Tags (Requirement 5.3) -->
    @php
        // Get related tags (tags that appear on posts with this tag)
        $relatedTags = \App\Models\Tag::whereHas('posts', function($query) use ($tag) {
            $query->whereIn('posts.id', $tag->posts()->pluck('posts.id'));
        })
        ->where('id', '!=', $tag->id)
        ->withCount('posts')
        ->orderByDesc('posts_count')
        ->limit(10)
        ->get();
    @endphp
    
    @if($relatedTags->isNotEmpty())
        <div class="mb-6">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Related Tags</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($relatedTags as $relatedTag)
                    <a 
                        href="{{ route('tag.show', $relatedTag->slug) }}"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm"
                    >
                        #{{ $relatedTag->name }}
                        <span class="text-xs text-gray-500 dark:text-gray-400">({{ $relatedTag->posts_count }})</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Filtering Options (Requirement 5.3) -->
    <div class="mb-6 flex items-center justify-between">
        <x-post-filters :current-url="route('tag.show', $tag->slug)" />
        
        <x-discovery.sort-dropdown 
            :current-sort="request('sort', 'newest')"
            :query="''"
            :filters="[]"
        />
    </div>

    <!-- Tagged Posts -->
    @if($posts->total() > 0)
        <x-infinite-scroll :posts="$posts" container-class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <div data-post-item>
                    <x-content.post-card :post="$post" />
                </div>
            @endforeach
        </x-infinite-scroll>
    @else
        <x-ui.empty-state 
            icon="tag"
            title="No articles yet"
            description="There are no published articles with this tag yet. Check back soon!"
        />
    @endif
</div>
@endsection

