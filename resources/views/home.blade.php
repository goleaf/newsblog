@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ loading: false }" x-init="loading = false">
    <!-- Hero Section -->
    @if($featuredPosts->count() > 0)
        <div class="mb-12">
            <x-content.hero-post :post="$featuredPosts->first()" />
        </div>
    @endif

    <!-- Trending Section -->
    @if($trendingPosts->count() > 0)
        <div class="mb-12">
            <x-content.trending-posts :posts="$trendingPosts" :limit="6" />
        </div>
    @endif

    <!-- Latest Articles Grid -->
    <div class="mb-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Latest Articles</h2>
            
            <!-- Sort Options -->
            <div class="flex items-center gap-2">
                <label for="sort" class="text-sm text-gray-600 dark:text-gray-400">Sort by:</label>
                <select 
                    id="sort"
                    class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    onchange="window.location.href = '/?sort=' + this.value"
                >
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest First</option>
                    <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>Most Popular</option>
                    <option value="trending" {{ request('sort') === 'trending' ? 'selected' : '' }}>Trending</option>
                </select>
            </div>
        </div>
        
        <x-content.post-grid :posts="$recentPosts" :columns="3" />
        
        <!-- Pagination -->
        @if($recentPosts->hasPages())
            <div class="mt-8">
                {{ $recentPosts->links() }}
            </div>
        @endif
    </div>

    <!-- Category Showcase -->
    @if($categories->count() > 0)
        <div class="mb-12">
            <x-discovery.category-grid :categories="$categories" />
        </div>
    @endif
</div>
@endsection

