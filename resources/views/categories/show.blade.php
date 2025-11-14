@extends('layouts.app')

@section('title', $category->meta_title ?? $category->name . ' - Articles')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumbs (Requirement 5.1) -->
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
                <a href="{{ route('category.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">
                    Categories
                </a>
            </li>
            @if($category->parent)
                <li>
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </li>
                <li>
                    <a href="{{ route('category.show', $category->parent->slug) }}" class="hover:text-gray-700 dark:hover:text-gray-300">
                        {{ $category->parent->name }}
                    </a>
                </li>
            @endif
            <li>
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
            </li>
            <li class="text-gray-900 dark:text-white font-medium" aria-current="page">
                {{ $category->name }}
            </li>
        </ol>
    </nav>

    <!-- Category Header (Requirement 5.1) -->
    <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-start gap-4">
            @if($category->icon)
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 rounded-lg flex items-center justify-center text-3xl" style="background-color: {{ $category->color_code ?? '#6366f1' }}20;">
                        {{ $category->icon }}
                    </div>
                </div>
            @endif
            <div class="flex-1">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ $category->name }}
                </h1>
                @if($category->description)
                    <p class="text-lg text-gray-600 dark:text-gray-300 mb-4">
                        {{ $category->description }}
                    </p>
                @endif
                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        {{ $posts->total() }} {{ Str::plural('article', $posts->total()) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Subcategory Navigation (Requirement 5.4) -->
    @if($category->children->isNotEmpty())
        <div class="mb-6">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Subcategories</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($category->children as $child)
                    <a 
                        href="{{ route('category.show', $child->slug) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        @if($child->icon)
                            <span>{{ $child->icon }}</span>
                        @endif
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $child->name }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">({{ $child->posts_count ?? 0 }})</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Post Filters and Sorting (Requirements 5.2, 26.1-26.5) -->
    <div class="mb-6 flex items-center justify-between">
        <x-post-filters :current-url="route('category.show', $category->slug)" />
        
        <x-discovery.sort-dropdown 
            :current-sort="request('sort', 'newest')"
            :query="''"
            :filters="[]"
        />
    </div>

    <!-- Posts Grid (Requirement 5.2) -->
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
            icon="document"
            title="No articles yet"
            description="There are no published articles in this category yet. Check back soon!"
        />
    @endif
</div>
@endsection

