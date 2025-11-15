<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('System Monitoring') }}
            </h2>
            <form method="POST" action="{{ route('admin.monitoring.reset') }}">
                @csrf
                <button 
                    type="submit" 
                    class="rounded-md bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
                    onclick="return confirm('Are you sure you want to reset all metrics?')"
                >
                    Reset Metrics
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <!-- Alerts -->
            @if(count($alerts) > 0)
                <div class="overflow-hidden rounded-lg bg-red-50 dark:bg-red-900/20 shadow-sm">
                    <div class="border-b border-red-200 bg-red-100 px-6 py-4 dark:border-red-800 dark:bg-red-900/30">
                        <h3 class="text-lg font-semibold text-red-900 dark:text-red-100">
                            ⚠️ Active Alerts ({{ count($alerts) }})
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach($alerts as $alert)
                                <div class="flex items-start gap-3 rounded-lg border-l-4 
                                    {{ $alert['severity'] === 'high' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' }} 
                                    p-4">
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $alert['message'] }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Type: {{ $alert['type'] }} | Severity: {{ $alert['severity'] }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Key Metrics Grid -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <!-- Post Views -->
                <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Post Views Tracked</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ number_format($metrics['post_views']['total']) }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($metrics['post_views']['queued']) }} queued
                                </p>
                            </div>
                            <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/30">
                                <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DNT Compliance -->
                <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">DNT Compliance Rate</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ $dntRate }}%
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($metrics['dnt']['enabled']) }} requests honored
                                </p>
                            </div>
                            <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/30">
                                <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Engagement Metrics -->
                <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Engagement Events</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ number_format($metrics['engagement']['total']) }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $authenticatedRate }}% authenticated
                                </p>
                            </div>
                            <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/30">
                                <svg class="h-8 w-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search Quality -->
                <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Search Quality</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ 100 - $zeroResultRate }}%
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($metrics['search']['total']) }} searches
                                </p>
                            </div>
                            <div class="rounded-full bg-indigo-100 p-3 dark:bg-indigo-900/30">
                                <svg class="h-8 w-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Metrics -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Post View Performance -->
                <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Post View Performance</h3>
                    </div>
                    <div class="p-6">
                        @if($metrics['post_views']['latest'])
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Latest Duration:</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $metrics['post_views']['latest']['duration_ms'] }}ms
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Post ID:</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        #{{ $metrics['post_views']['latest']['post_id'] }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Queued:</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $metrics['post_views']['latest']['queued'] ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Timestamp:</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $metrics['post_views']['latest']['timestamp'] }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No recent tracking data</p>
                        @endif
                    </div>
                </div>

                <!-- Search Performance -->
                <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Search Performance</h3>
                    </div>
                    <div class="p-6">
                        @if($metrics['search']['latest'])
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Latest Query:</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        "{{ Str::limit($metrics['search']['latest']['query'], 30) }}"
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Results:</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $metrics['search']['latest']['result_count'] }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Duration:</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $metrics['search']['latest']['duration_ms'] }}ms
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Timestamp:</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $metrics['search']['latest']['timestamp'] }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No recent search data</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Error Tracking -->
            <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Error Tracking</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total Errors</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($metrics['errors']['total']) }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Tracking Errors</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($metrics['errors']['tracking']) }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Database Errors</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($metrics['errors']['database']) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
