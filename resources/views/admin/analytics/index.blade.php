@extends('layouts.app')

@section('title', 'Analytics Dashboard')

@section('content')
@push('page-scripts')
    <x-page-scripts page="analytics-dashboard" />
@endpush
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            Analytics Dashboard
        </h1>
        
        {{-- Period Selector --}}
        <div class="flex items-center gap-2">
            <label for="period" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Period:
            </label>
            <select 
                id="period" 
                name="period"
                onchange="window.location.href = '{{ route('admin.analytics') }}?period=' + this.value"
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
                <option value="day" {{ $period === 'day' ? 'selected' : '' }}>Last 24 Hours</option>
                <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Last Week</option>
                <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Last Month</option>
                <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Last Year</option>
            </select>
        </div>
    </div>

    {{-- View Statistics --}}
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            View Statistics
        </h2>
        {{-- Views over time (last 30 days when period=month) --}}
        <div 
            id="views-over-time-chart" 
            class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6"
            data-views-over-time='@json($viewStats["views_over_time"])'
        >
            <canvas id="viewsOverTimeCanvas" height="120"></canvas>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Views</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ number_format($viewStats['total_views']) }}
                        </p>
                    </div>
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-full">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Unique Visitors</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ number_format($viewStats['unique_visitors']) }}
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Posts Viewed</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ number_format($viewStats['posts_viewed']) }}
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Engagement Metrics --}}
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Engagement Metrics
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Time on Page</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ gmdate('i:s', $engagementStats['avg_time_on_page']) }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Scroll Depth</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $engagementStats['avg_scroll_depth'] }}%
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Engagement Rate</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $engagementStats['engagement_rate'] }}%
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Interactions</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ number_format($engagementStats['bookmark_clicks'] + $engagementStats['share_clicks'] + $engagementStats['reaction_clicks'] + $engagementStats['comment_clicks']) }}
                </p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Bookmarks</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ number_format($engagementStats['bookmark_clicks']) }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Shares</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ number_format($engagementStats['share_clicks']) }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Reactions</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ number_format($engagementStats['reaction_clicks']) }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Comments</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ number_format($engagementStats['comment_clicks']) }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Related Posts</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ number_format($engagementStats['related_post_clicks']) }}
                </p>
            </div>
        </div>
    </div>

    {{-- Search Analytics --}}
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Search Analytics
        </h2>
        <p class="sr-only">Search metrics for the selected period.</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Searches</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ number_format($searchStats['total_searches']) }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Click-Through Rate</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $clickThroughRate }}%
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">No Results Rate</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $searchStats['no_result_percentage'] }}%
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Top Search Queries
                </h3>
                <div class="space-y-3">
                    @forelse($topQueries as $query)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $query->query }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $query->count }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No search data available</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Queries with No Results
                </h3>
                <div class="space-y-3">
                    @forelse($noResultQueries as $query)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $query->query }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $query->count }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No data available</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Top Performing Posts --}}
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Top Performing Posts
        </h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Post
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Category
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Author
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Views
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($topPosts as $post)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('post.show', $post->slug) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ Str::limit($post->title, 50) }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                {{ $post->category->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                {{ $post->user->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ number_format($post->views_count) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                No data available
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Popular Categories --}}
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Most Popular Categories
        </h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($popularCategories as $cat)
                    <div class="flex items-center justify-between p-4 rounded border border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $cat->name }}</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($cat->views) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No data available</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Traffic Sources --}}
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Traffic Sources
        </h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <dl class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="p-4 rounded border border-gray-200 dark:border-gray-700">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Direct</dt>
                    <dd class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($trafficSources['direct']) }}</dd>
                </div>
                <div class="p-4 rounded border border-gray-200 dark:border-gray-700">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Search</dt>
                    <dd class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($trafficSources['search']) }}</dd>
                </div>
                <div class="p-4 rounded border border-gray-200 dark:border-gray-700">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Social</dt>
                    <dd class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($trafficSources['social']) }}</dd>
                </div>
                <div class="p-4 rounded border border-gray-200 dark:border-gray-700">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Referral</dt>
                    <dd class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($trafficSources['referral']) }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
