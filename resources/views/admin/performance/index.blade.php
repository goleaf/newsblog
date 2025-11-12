<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Performance Monitoring') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Memory Usage Alert -->
            @if($memory['alert'])
                <div class="mb-6 rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                High Memory Usage Alert
                            </h3>
                            <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                <p>Memory usage is at {{ $memory['percentage'] }}% ({{ $memory['usage_formatted'] }} / {{ $memory['limit_formatted'] }})</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Memory Usage Card -->
            <div class="mb-6 overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="mb-4 text-lg font-semibold">Memory Usage</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Current Usage</p>
                            <p class="text-2xl font-bold">{{ $memory['usage_formatted'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Memory Limit</p>
                            <p class="text-2xl font-bold">{{ $memory['limit_formatted'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Usage Percentage</p>
                            <p class="text-2xl font-bold {{ $memory['alert'] ? 'text-red-600' : 'text-green-600' }}">
                                {{ $memory['percentage'] }}%
                            </p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="h-4 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-4 rounded-full {{ $memory['alert'] ? 'bg-red-600' : 'bg-green-600' }}" 
                                 style="width: {{ min($memory['percentage'], 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Load Times Card -->
            <div class="mb-6 overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="mb-4 text-lg font-semibold">Average Page Load Time (Last 24 Hours)</h3>
                    @if(count($pageLoads) > 0)
                        <div class="overflow-x-auto">
                            <canvas id="pageLoadChart" class="w-full" height="80"></canvas>
                        </div>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Hour</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Average Load Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Requests</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    @foreach($pageLoads as $load)
                                        <tr>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">{{ $load['hour'] }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">{{ $load['average'] }} ms</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">{{ $load['count'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-600 dark:text-gray-400">No page load data available yet.</p>
                    @endif
                </div>
            </div>

            <!-- Cache Statistics Card -->
            <div class="mb-6 overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="mb-4 text-lg font-semibold">Cache Hit/Miss Ratio (Last 7 Days)</h3>
                    @if(count($cacheStats) > 0)
                        <div class="overflow-x-auto">
                            <canvas id="cacheStatsChart" class="w-full" height="80"></canvas>
                        </div>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Hits</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Misses</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Hit Ratio</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    @foreach($cacheStats as $stat)
                                        <tr>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">{{ $stat['date'] }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">{{ number_format($stat['hits']) }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">{{ number_format($stat['misses']) }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                                    {{ $stat['ratio'] >= 80 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                                       ($stat['ratio'] >= 60 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                                       'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                                    {{ $stat['ratio'] }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-600 dark:text-gray-400">No cache statistics available yet.</p>
                    @endif
                </div>
            </div>

            <!-- Slow Queries Card -->
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="mb-4 text-lg font-semibold">Slow Queries (>100ms)</h3>
                    @if(count($slowQueries) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Time (ms)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Query</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    @foreach($slowQueries as $query)
                                        <tr>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                                    {{ $query['time'] >= 500 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                                       ($query['time'] >= 200 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                                       'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200') }}">
                                                    {{ number_format($query['time'], 2) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <code class="block max-w-2xl overflow-x-auto rounded bg-gray-100 p-2 text-xs dark:bg-gray-900">{{ $query['sql'] }}</code>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">{{ \Carbon\Carbon::parse($query['timestamp'])->format('Y-m-d H:i:s') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-600 dark:text-gray-400">No slow queries detected. Great performance!</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Page Load Chart
        @if(count($pageLoads) > 0)
        const pageLoadCtx = document.getElementById('pageLoadChart').getContext('2d');
        new Chart(pageLoadCtx, {
            type: 'line',
            data: {
                labels: @json(array_column($pageLoads, 'hour')),
                datasets: [{
                    label: 'Average Load Time (ms)',
                    data: @json(array_column($pageLoads, 'average')),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Milliseconds'
                        }
                    }
                }
            }
        });
        @endif

        // Cache Stats Chart
        @if(count($cacheStats) > 0)
        const cacheStatsCtx = document.getElementById('cacheStatsChart').getContext('2d');
        new Chart(cacheStatsCtx, {
            type: 'bar',
            data: {
                labels: @json(array_column($cacheStats, 'date')),
                datasets: [
                    {
                        label: 'Cache Hits',
                        data: @json(array_column($cacheStats, 'hits')),
                        backgroundColor: 'rgba(34, 197, 94, 0.5)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1
                    },
                    {
                        label: 'Cache Misses',
                        data: @json(array_column($cacheStats, 'misses')),
                        backgroundColor: 'rgba(239, 68, 68, 0.5)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Count'
                        }
                    }
                }
            }
        });
        @endif
    </script>
    @endpush
</x-app-layout>
