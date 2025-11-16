@extends('layouts.app')

@php
    $metaTags = $tag->getMetaTags();
    $structuredData = $tag->getStructuredData();
@endphp

@push('meta-tags')
    {{-- Basic Meta Tags --}}
    <title>{{ $metaTags['title'] }} | {{ config('app.name') }}</title>
    <meta name="description" content="{{ $metaTags['description'] }}">
    @if(!empty($metaTags['keywords']))
        <meta name="keywords" content="{{ $metaTags['keywords'] }}">
    @endif

    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">

    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ $metaTags['og:url'] }}">

    {{-- Open Graph Meta Tags --}}
    <meta property="og:title" content="{{ $metaTags['og:title'] }}">
    <meta property="og:description" content="{{ $metaTags['og:description'] }}">
    <meta property="og:image" content="{{ $metaTags['og:image'] }}">
    <meta property="og:url" content="{{ $metaTags['og:url'] }}">
    <meta property="og:type" content="{{ $metaTags['og:type'] }}">
    <meta property="og:site_name" content="{{ $metaTags['og:site_name'] }}">

    {{-- Twitter Card Meta Tags --}}
    <meta name="twitter:card" content="{{ $metaTags['twitter:card'] }}">
    <meta name="twitter:title" content="{{ $metaTags['twitter:title'] }}">
    <meta name="twitter:description" content="{{ $metaTags['twitter:description'] }}">
    <meta name="twitter:image" content="{{ $metaTags['twitter:image'] }}">
    <meta name="twitter:url" content="{{ $metaTags['twitter:url'] }}">
@endpush

@push('structured-data')
    {{-- Schema.org CollectionPage Structured Data (JSON-LD) --}}
    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- BreadcrumbList Structured Data --}}
    @if(isset($breadcrumbStructuredData))
        <script type="application/ld+json">
            {!! $breadcrumbStructuredData !!}
        </script>
    @endif
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumbs (Requirement 5.1) -->
    @if(isset($breadcrumbs))
        <x-breadcrumbs :breadcrumbs="$breadcrumbs" :structured-data="$breadcrumbStructuredData ?? null" />
    @endif

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
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                {{ __('Related Tags') }}
            </h2>
            <div class="flex flex-wrap gap-2">
                @foreach($relatedTags as $relatedTag)
                    <a 
                        href="{{ route('tag.show', $relatedTag->slug) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 rounded-full hover:bg-indigo-100 dark:hover:bg-indigo-900/30 hover:text-indigo-800 dark:hover:text-indigo-200 transition-all text-sm font-medium group"
                    >
                        <span class="text-indigo-500 dark:text-indigo-400">#</span>
                        <span>{{ $relatedTag->name }}</span>
                        <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-100 dark:bg-indigo-900/40 rounded-full group-hover:bg-indigo-200 dark:group-hover:bg-indigo-900/60">
                            {{ $relatedTag->posts_count }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Filtering Options (Requirement 5.3) -->
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <x-post-filters :current-url="route('tag.show', $tag->slug)" />
        
        <x-tag-sort-dropdown 
            :current-sort="request('sort', 'latest')"
            :tag-slug="$tag->slug"
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
            title="{{ __('No articles found') }}"
            message="{{ __('There are no published articles with this tag') }}{{ request('date_filter') ? ' '. __('for the selected time period') : '' }}. {{ request('date_filter') ? __('Try adjusting your filters or') : '' }} {{ __('Explore other tags to discover more content!') }}"
            actionText="{{ __('Browse All Articles') }}"
            actionUrl="{{ route('home') }}"
        >
            <x-slot:icon>
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
            </x-slot:icon>
        </x-ui.empty-state>
    @endif
</div>
@endsection

