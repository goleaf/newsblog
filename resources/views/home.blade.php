@extends('layouts.app', ['page' => 'home'])

@section('title', __('home.title'))

@push('page-scripts')
    <x-page-scripts page="homepage" />
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ loading: true }" x-init="(window.requestIdleCallback ? requestIdleCallback(() => loading = false) : setTimeout(() => loading = false, 0))">
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
                    <div x-show="loading" class="fade-in is-visible">
                        <x-ui.skeleton-loader type="image" class="h-[500px] rounded-lg mb-4" />
                        <x-ui.skeleton-loader type="text" :count="3" />
                    </div>
                    <div x-show="!loading" x-transition.opacity>
                        <x-content.hero-post :post="$featuredPosts->first()" />
                    </div>
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
                    <div x-show="loading">
                        <x-ui.skeleton-loader type="list" :count="3" />
                    </div>
                    <div x-show="!loading" x-transition.opacity>
                        <x-content.trending-posts :posts="$trendingPosts" :limit="6" />
                    </div>
                </div>
            @endif

            <!-- Editor's Picks Section -->
            @if(isset($editorsPicks) && $editorsPicks->count() > 0)
                <div class="mb-12">
                    <div x-show="loading">
                        <x-ui.skeleton-loader type="card" :count="6" />
                    </div>
                    <div x-show="!loading" x-transition.opacity>
                        <x-content.editors-picks :posts="$editorsPicks" />
                    </div>
                </div>
            @endif

            <!-- Category-Based Content Sections -->
            @if(isset($categorySections) && $categorySections->isNotEmpty())
                <div class="mb-12">
                    <div x-show="loading">
                        <x-ui.skeleton-loader type="card" :count="4" />
                    </div>
                    <div x-show="!loading" x-transition.opacity>
                        <x-content.category-sections :categorySections="$categorySections" />
                    </div>
                </div>
            @endif

            <!-- Latest Articles Grid -->
            <div class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('home.latest_articles') }}</h2>
                    
                    <!-- Sort Options -->
                    <div class="flex items-center gap-2">
                        <label for="sort" class="text-sm text-gray-600 dark:text-gray-400">{{ __('home.sort_by') }}</label>
                        <select 
                            id="sort"
                            class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            onchange="window.location.href = '/?sort=' + this.value"
                        >
                            <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>{{ __('home.sort_newest') }}</option>
                            <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>{{ __('home.sort_popular') }}</option>
                            <option value="trending" {{ request('sort') === 'trending' ? 'selected' : '' }}>{{ __('home.sort_trending') }}</option>
                        </select>
                    </div>
                </div>
                
                <div x-show="loading">
                    <x-ui.skeleton-loader type="card" :count="6" />
                </div>
                <div x-show="!loading" x-transition.opacity>
                    @if($recentPosts->total() > 0)
                        <x-infinite-scroll :posts="$recentPosts" container-class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($recentPosts as $post)
                                <div data-post-item>
                                    <x-content.post-card :post="$post" />
                                </div>
                            @endforeach
                        </x-infinite-scroll>
                    @else
                        <x-ui.empty-state 
                            title="{{ __('home.empty_latest_title') }}"
                            message="{{ __('home.empty_latest_message') }}"
                            actionText="{{ __('home.empty_latest_action') }}"
                            actionUrl="{{ route('home') }}"
                        />
                    @endif
                </div>

                <!-- Pagination (No-JS fallback) -->
                <noscript>
                    @if($recentPosts->hasPages())
                        <div class="mt-8">
                            {{ $recentPosts->links() }}
                        </div>
                    @endif
                </noscript>
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
                    <div x-show="loading">
                        <x-ui.skeleton-loader type="list" :count="5" />
                    </div>
                    <div x-show="!loading" x-transition.opacity>
                        <x-widgets.most-popular :posts="$mostPopular" />
                    </div>
                @endif

                <!-- Trending Now Widget -->
                @if(isset($trendingNow) && $trendingNow->count() > 0)
                    <div x-show="loading">
                        <x-ui.skeleton-loader type="list" :count="5" />
                    </div>
                    <div x-show="!loading" x-transition.opacity>
                        <x-widgets.trending-now :posts="$trendingNow" />
                    </div>
                @endif

                <!-- Additional Widget Areas -->
                <x-layout.sidebar :sticky="true" />
            </div>
        </aside>
    </div>
</div>
@endsection

