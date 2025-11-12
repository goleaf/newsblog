<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Search Analytics') }}
            </h2>
            <a href="{{ route('admin.search') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                &larr; Back to Search
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            
            <!-- Performance Metrics -->
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Performance Metrics</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Last 24 hours</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Searches</div>
                            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($metrics['total_searches'] ?? 0) }}
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Response Time</div>
                            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($metrics['avg_execution_time'] ?? 0, 0) }}ms
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Cache Hit Rate</div>
                            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($metrics['cache_hit_rate'] ?? 0, 1) }}%
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">No Results Rate</div>
                            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($metrics['no_results_rate'] ?? 0, 1) }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Queries -->
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Top Search Queries</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Most popular searches in the last 30 days</p>
                </div>
                <div class="p-6">
                    @if($topQueries->isEmpty())
                        <p class="text-gray-600 dark:text-gray-400">No search data available yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Query
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Count
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Avg Results
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Avg Time
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($topQueries as $query)
                                        <tr>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $query->query }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {{ number_format($query->search_count) }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {{ number_format($query->avg_results, 1) }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {{ number_format($query->avg_time, 0) }}ms
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- No Results Queries -->
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Queries with No Results</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Content gaps to address</p>
                </div>
                <div class="p-6">
                    @if($noResultQueries->isEmpty())
                        <p class="text-gray-600 dark:text-gray-400">All searches are returning results!</p>
                    @else
                        <div class="space-y-3">
                            @foreach($noResultQueries as $query)
                                <div class="flex items-center justify-between rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $query->query }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            Searched {{ $query->search_count }} {{ Str::plural('time', $query->search_count) }}
                                            • Last: {{ $query->last_searched->diffForHumans() }}
                                        </div>
                                    </div>
                                    <a href="{{ route('search', ['q' => $query->query]) }}" 
                                       class="ml-4 text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                                       target="_blank">
                                        Test &rarr;
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Search Trends Chart -->
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Search Volume Trend</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Last 7 days</p>
                </div>
                <div class="p-6">
                    <div class="h-64">
                        <canvas id="searchTrendChart"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('searchTrendChart');
            if (ctx) {
                const chartData = @json($chartData ?? []);
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels || [],
                        datasets: [{
                            label: 'Total Searches',
                            data: chartData.searches || [],
                            borderColor: 'rgb(99, 102, 241)',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'No Results',
                            data: chartData.no_results || [],
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
