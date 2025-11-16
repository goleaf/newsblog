@extends('layouts.app', ['page' => 'home'])

@section('title', 'Home')

@push('page-scripts')
    <x-page-scripts page="homepage" />
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ loading: false }" x-init="loading = false">
    <!-- Breaking News Ticker -->
    @if(isset($breakingNews) && $breakingNews->count() > 0)
        <div class="mb-6 -mx-4 sm:-mx-6 lg:-mx-8">
            <x-content.breaking-news-ticker :posts="$breakingNews" />
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-3">
            <!-- Hero Section with Featured Post -->
            @if($featuredPosts->count() > 0)
                <div class="mb-12">
                    <x-content.hero-post :post="$featuredPosts->first()" />
                </div>
            @endif

            <!-- Breaking News Section -->
            @if(isset($breakingNews) && $breakingNews->count() > 0)
                <div class="mb-12">
                    <x-content.breaking-news :posts="$breakingNews" />
                </div>
            @endif

            <!-- Trending Section -->
            @if($trendingPosts->count() > 0)
                <div class="mb-12">
                    <x-content.trending-posts :posts="$trendingPosts" :limit="6" />
                </div>
            @endif

            <!-- Category-Based Content Sections -->
            @if(isset($categorySections) && $categorySections->isNotEmpty())
                <div class="mb-12">
                    <x-content.category-sections :categorySections="$categorySections" />
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

        <!-- Sidebar -->
        <aside class="lg:col-span-1">
            <div class="space-y-6">
                <!-- Most Popular Widget -->
                @if(isset($mostPopular) && $mostPopular->count() > 0)
                    <x-widgets.most-popular :posts="$mostPopular" />
                @endif

                <!-- Trending Now Widget -->
                @if(isset($trendingNow) && $trendingNow->count() > 0)
                    <x-widgets.trending-now :posts="$trendingNow" />
                @endif

                <!-- Additional Widget Areas -->
                <x-layout.sidebar :sticky="true" />
            </div>
        </aside>
    </div>
</div>
@endsection

