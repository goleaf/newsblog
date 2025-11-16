<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @push('page-scripts')
        <x-page-scripts page="dashboard" />
    @endpush

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(isset($stats))
                <!-- User Dashboard -->
                <!-- Welcome Message -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ __('dashboard.welcome_back') }}, {{ auth()->user()->name }}!
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        {{ __('dashboard.overview_text') }}
                    </p>
                </div>

                <!-- Stats Cards -->
                <x-user.stats-cards :stats="$stats" />

                <!-- Quick Links -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <a href="{{ route('bookmarks.index') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover:shadow-lg transition-all group">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                    My Bookmarks
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    View and manage your saved articles
                                </p>
                            </div>
                            <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>

                    <a href="{{ route('profile.edit') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover:shadow-lg transition-all group">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                    Edit Profile
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Update your profile information
                                </p>
                            </div>
                            <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                </div>

                <!-- Activity Feed -->
                <x-user.activity-feed 
                    :recent-bookmarks="$recentBookmarks" 
                    :recent-comments="$recentComments" 
                    :recent-reactions="$recentReactions" 
                />

                <!-- Notification Summary -->
                @if(isset($notificationSummary))
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('dashboard.recent_notifications') }}
                            </h3>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('dashboard.unread_count') }}: {{ number_format($notificationSummary['unread_count'] ?? 0) }}
                            </span>
                        </div>
                        <div class="space-y-3">
                            @forelse(($notificationSummary['recent'] ?? []) as $n)
                                <div class="flex items-start justify-between p-3 rounded-md bg-gray-50 dark:bg-gray-700/40">
                                    <div class="pr-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $n->title }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $n->message }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $n->created_at->diffForHumans() }}</div>
                                    </div>
                                    @if($n->action_url)
                                        <a class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline" href="{{ $n->action_url }}">
                                            {{ __('dashboard.view') }}
                                        </a>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.no_notifications') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                <!-- Reading History -->
                @if(isset($readingHistory))
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
                        <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('dashboard.reading_history') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.reading_history_subtitle') }}</p>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                @forelse($readingHistory as $view)
                                    @if($view->post)
                                        <a href="{{ route('post.show', $view->post->slug) }}" class="flex items-center justify-between p-3 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                    {{ $view->post->title }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ __('dashboard.viewed_at') }}: {{ $view->viewed_at?->diffForHumans() }}
                                                </div>
                                            </div>
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    @endif
                                @empty
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.no_reading_history') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif

            @elseif(isset($metrics))
                <!-- Admin Dashboard -->
                <!-- Key Metrics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total Posts -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Posts</p>
                                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                                        {{ number_format($metrics['total_posts']) }}
                                    </p>
                                    <div class="flex items-center mt-2">
                                        @if($metrics['posts_comparison']['is_increase'])
                                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-sm text-green-600 dark:text-green-400 ml-1">
                                                {{ $metrics['posts_comparison']['percentage'] }}%
                                            </span>
                                        @else
                                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-sm text-red-600 dark:text-red-400 ml-1">
                                                {{ abs($metrics['posts_comparison']['percentage']) }}%
                                            </span>
                                        @endif
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">vs last 30 days</span>
                                    </div>
                                </div>
                                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Views Today -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Views Today</p>
                                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                                        {{ number_format($metrics['views_today']) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                        {{ number_format($metrics['views_week']) }} this week
                                    </p>
                                </div>
                                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
                                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Views This Month -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Views This Month</p>
                                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                                        {{ number_format($metrics['views_month']) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                        Last 30 days
                                    </p>
                                </div>
                                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Comments -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Comments</p>
                                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                                        {{ number_format($metrics['pending_comments']) }}
                                    </p>
                                    @if($metrics['pending_comments'] > 0)
                                        <p class="text-xs text-orange-600 dark:text-orange-400 mt-2">
                                            Needs review
                                        </p>
                                    @else
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                            All caught up!
                                        </p>
                                    @endif
                                </div>
                                <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-full">
                                    <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Posts Published Chart -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Posts Published</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Last 30 days</p>
                    </div>
                    <div class="p-6">
                        <canvas
                            id="postsChart"
                            height="80"
                            data-labels='@json($metrics["posts_chart_data"]["labels"])'
                            data-values='@json($metrics["posts_chart_data"]["data"])'
                        ></canvas>
                    </div>
                </div>

                <!-- Top Posts by Views -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Top Posts by Views</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Most viewed content</p>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Rank
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Title
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Views
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Published
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse($metrics['top_posts'] as $index => $post)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $index < 3 ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }} font-semibold text-sm">
                                                    {{ $index + 1 }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $post['title'] }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ number_format($post['view_count']) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $post['published_at'] }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                                No posts published yet
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        {{ __("You're logged in!") }}
                    </div>
                </div>
            @endif

            @if($searchStats)
                <!-- Search Statistics Widget -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Search Statistics</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Recent search activity</p>
                        </div>
                        <a href="{{ route('admin.search.analytics') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                            View Full Analytics &rarr;
                        </a>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                                <div class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Searches Today</div>
                                <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ number_format($searchStats['total_today']) }}
                                </div>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <div class="text-sm font-medium text-green-600 dark:text-green-400">Popular Queries</div>
                                <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ $searchStats['popular_queries']->count() }}
                                </div>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Recent Activity</div>
                                <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ $searchStats['recent_searches']->count() }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Recent Searches -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Recent Searches</h4>
                                <div class="space-y-2">
                                    @forelse($searchStats['recent_searches'] as $search)
                                        <div class="flex items-center justify-between text-sm">
                                            <div class="flex-1 truncate">
                                                <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $search->query }}</span>
                                                <span class="text-gray-500 dark:text-gray-400 text-xs ml-2">
                                                    ({{ $search->result_count }} results)
                                                </span>
                                            </div>
                                            <span class="text-gray-400 dark:text-gray-500 text-xs ml-2">
                                                {{ $search->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No recent searches</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Popular Queries -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Popular Queries (Last 7 Days)</h4>
                                <div class="space-y-2">
                                    @forelse($searchStats['popular_queries'] as $query)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-900 dark:text-gray-100 font-medium truncate flex-1">
                                                {{ $query->query }}
                                            </span>
                                            <span class="text-indigo-600 dark:text-indigo-400 font-semibold ml-2">
                                                {{ number_format($query->count) }}
                                            </span>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No popular queries yet</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('page-scripts')
        <x-page-scripts page="dashboard" />
    @endpush
</x-app-layout>
