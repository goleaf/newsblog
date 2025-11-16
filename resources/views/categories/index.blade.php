@extends('layouts.app')

@section('title', 'Browse Categories')

@push('meta-tags')
    <title>Browse Categories | {{ config('app.name') }}</title>
    <meta name="description" content="Explore all categories and topics on {{ config('app.name') }}. Find articles on programming, web development, mobile development, and more.">
    <meta name="robots" content="index, follow">
    
    <link rel="canonical" href="{{ route('categories.index') }}">
    
    <meta property="og:title" content="Browse Categories | {{ config('app.name') }}">
    <meta property="og:description" content="Explore all categories and topics on {{ config('app.name') }}.">
    <meta property="og:url" content="{{ route('categories.index') }}">
    <meta property="og:type" content="website">
    
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Browse Categories | {{ config('app.name') }}">
    <meta name="twitter:description" content="Explore all categories and topics on {{ config('app.name') }}.">
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
            Browse Categories
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-300">
            Explore articles organized by topic and category
        </p>
    </div>

    <!-- Categories Grid -->
    @if($categories->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($categories as $category)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <a href="{{ route('category.show', $category->slug) }}" class="block p-6">
                        <div class="flex items-start gap-4 mb-4">
                            @if($category->icon)
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-lg flex items-center justify-center text-2xl" style="background-color: {{ $category->color_code ?? '#6366f1' }}20;">
                                        {{ $category->icon }}
                                    </div>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-1 truncate">
                                    {{ $category->name }}
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $category->posts_count ?? 0 }} {{ Str::plural('article', $category->posts_count ?? 0) }}
                                </p>
                            </div>
                        </div>
                        
                        @if($category->description)
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 line-clamp-2">
                                {{ $category->description }}
                            </p>
                        @endif

                        <!-- Subcategories -->
                        @if($category->children->isNotEmpty())
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">
                                    Subcategories:
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($category->children->take(3) as $child)
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-700 dark:text-gray-300">
                                            @if($child->icon)
                                                <span>{{ $child->icon }}</span>
                                            @endif
                                            {{ $child->name }}
                                            <span class="text-gray-500 dark:text-gray-400">({{ $child->posts_count ?? 0 }})</span>
                                        </span>
                                    @endforeach
                                    @if($category->children->count() > 3)
                                        <span class="inline-flex items-center px-2 py-1 text-xs text-gray-500 dark:text-gray-400">
                                            +{{ $category->children->count() - 3 }} more
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <x-ui.empty-state 
            title="No categories found"
            message="There are no categories available at the moment. Check back soon!"
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
