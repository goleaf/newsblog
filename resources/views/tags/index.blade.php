@extends('layouts.app')

@section('title', 'Browse Tags')

@push('meta-tags')
    <title>Browse Tags | {{ config('app.name') }}</title>
    <meta name="description" content="Explore all tags and topics on {{ config('app.name') }}. Find articles by specific technologies, frameworks, and programming concepts.">
    <meta name="robots" content="index, follow">
    
    <link rel="canonical" href="{{ route('tags.index') }}">
    
    <meta property="og:title" content="Browse Tags | {{ config('app.name') }}">
    <meta property="og:description" content="Explore all tags and topics on {{ config('app.name') }}.">
    <meta property="og:url" content="{{ route('tags.index') }}">
    <meta property="og:type" content="website">
    
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Browse Tags | {{ config('app.name') }}">
    <meta name="twitter:description" content="Explore all tags and topics on {{ config('app.name') }}.">
@endpush

@push('structured-data')
    @if(isset($breadcrumbStructuredData))
        <script type="application/ld+json">
            {!! $breadcrumbStructuredData !!}
        </script>
    @endif
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumbs -->
    @if(isset($breadcrumbs))
        <x-breadcrumbs :breadcrumbs="$breadcrumbs" :structured-data="$breadcrumbStructuredData ?? null" />
    @endif

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-3">
            Browse Tags
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-300">
            Discover articles by specific technologies, frameworks, and programming concepts
        </p>
    </div>

    <!-- Tag Cloud -->
    @if($tags->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-wrap gap-3">
                @foreach($tags as $tag)
                    @php
                        // Calculate tag size based on post count (min: text-sm, max: text-2xl)
                        $maxCount = $tags->max('posts_count');
                        $minCount = $tags->min('posts_count');
                        $range = $maxCount - $minCount;
                        
                        if ($range > 0) {
                            $normalized = ($tag->posts_count - $minCount) / $range;
                        } else {
                            $normalized = 0.5;
                        }
                        
                        // Map to font sizes
                        $sizeClasses = [
                            'text-sm',
                            'text-base',
                            'text-lg',
                            'text-xl',
                            'text-2xl'
                        ];
                        $sizeIndex = (int) floor($normalized * (count($sizeClasses) - 1));
                        $sizeClass = $sizeClasses[$sizeIndex];
                        
                        // Color intensity based on popularity
                        $colorClasses = [
                            'text-indigo-400 dark:text-indigo-500',
                            'text-indigo-500 dark:text-indigo-400',
                            'text-indigo-600 dark:text-indigo-300',
                            'text-indigo-700 dark:text-indigo-200',
                            'text-indigo-800 dark:text-indigo-100'
                        ];
                        $colorClass = $colorClasses[$sizeIndex];
                    @endphp
                    
                    <a 
                        href="{{ route('tag.show', $tag->slug) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-full hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-all group {{ $sizeClass }} font-medium {{ $colorClass }}"
                        title="{{ $tag->posts_count }} {{ Str::plural('article', $tag->posts_count) }}"
                    >
                        <span class="text-indigo-500 dark:text-indigo-400">#</span>
                        <span>{{ $tag->name }}</span>
                        <span class="inline-flex items-center justify-center min-w-[24px] h-6 px-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-100 dark:bg-indigo-900/40 rounded-full group-hover:bg-indigo-200 dark:group-hover:bg-indigo-900/60">
                            {{ $tag->posts_count }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Tag Statistics -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $tags->count() }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Tags</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $tags->sum('posts_count') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tagged Articles</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $tags->isNotEmpty() ? number_format($tags->avg('posts_count'), 1) : 0 }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Avg. per Tag</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Tags List -->
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Most Popular Tags</h2>
            <div class="space-y-3">
                @foreach($tags->take(10) as $tag)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <a 
                            href="{{ route('tag.show', $tag->slug) }}"
                            class="flex items-center gap-2 text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                        >
                            <span class="text-indigo-500 dark:text-indigo-400">#</span>
                            <span class="font-medium">{{ $tag->name }}</span>
                        </a>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $tag->posts_count }} {{ Str::plural('article', $tag->posts_count) }}
                            </span>
                            <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div 
                                    class="bg-indigo-600 dark:bg-indigo-400 h-2 rounded-full transition-all"
                                    style="width: {{ ($tag->posts_count / $tags->max('posts_count')) * 100 }}%"
                                ></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <x-ui.empty-state 
            title="No tags found"
            message="There are no tags available at the moment. Check back soon!"
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
