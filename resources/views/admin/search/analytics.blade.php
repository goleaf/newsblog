@extends('admin.layouts.app')

@section('title', 'Search Analytics')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Search Analytics</h1>
        
        <div class="flex items-center gap-4">
            <form method="GET" action="{{ route('admin.search.analytics') }}" class="flex items-center gap-2">
                <label for="period" class="text-sm text-gray-600 dark:text-gray-400">Period:</label>
                <select name="period" id="period" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="day" {{ $period === 'day' ? 'selected' : '' }}>Today</option>
                    <option value="week" {{ $period === 'week' ? 'selected' : '' }}>This Week</option>
                    <option value="month" {{ $period === 'month' ? 'selected' : '' }}>This Month</option>
                    <option value="year" {{ $period === 'year' ? 'selected' : '' }}>This Year</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Total Searches</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($performanceMetrics['total_searches']) }}</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Avg Execution Time</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($performanceMetrics['avg_execution_time'], 2) }}ms</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">No Result Searches</h3>
            <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($performanceMetrics['no_result_searches']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ number_format($performanceMetrics['no_result_percentage'], 1) }}% of total</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Avg Results per Search</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($performanceMetrics['avg_result_count'], 1) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Top Queries -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Top Queries</h2>
            </div>
            <div class="p-6">
                @if($topQueries->isEmpty())
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">No search queries found.</p>
                @else
                    <div class="space-y-3">
                        @foreach($topQueries as $index => $query)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400 w-8">{{ $index + 1 }}.</span>
                                    <span class="text-gray-900 dark:text-white">{{ $query->query }}</span>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($query->count) }} searches</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- No Result Queries -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">No Result Queries</h2>
            </div>
            <div class="p-6">
                @if($noResultQueries->isEmpty())
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">No queries with zero results.</p>
                @else
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @foreach($noResultQueries as $index => $query)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400 w-8">{{ $index + 1 }}.</span>
                                    <span class="text-gray-900 dark:text-white">{{ $query->query }}</span>
                                </div>
                                <span class="text-sm text-red-600 dark:text-red-400">{{ number_format($query->count) }} times</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Searches -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Recent Searches</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Query</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Results</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Execution Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentSearches as $search)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $search->query }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm {{ $search->result_count === 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $search->result_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ number_format($search->execution_time, 2) }}ms
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $search->user ? $search->user->name : 'Guest' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $search->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No recent searches found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

