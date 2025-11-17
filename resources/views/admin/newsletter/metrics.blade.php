<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Newsletter Performance Metrics') }}
            </h2>
            <a href="{{ route('admin.newsletter.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Batch Info -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Batch Information</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Batch ID</dt>
                            <dd class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $batchId }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Subject</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $firstSend->subject }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Sent At</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $firstSend->created_at->format('M d, Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sent</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalSent) }}</div>
                        @if($totalFailed > 0)
                            <div class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $totalFailed }} failed</div>
                        @endif
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Open Rate</div>
                        <div class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $openRate }}%</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($totalOpened) }} opened</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Click Rate</div>
                        <div class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ $clickRate }}%</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($totalClicked) }} clicked</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Click-to-Open</div>
                        <div class="mt-2 text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $clickToOpenRate }}%</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($totalClicks) }} total clicks</div>
                    </div>
                </div>
            </div>

            <!-- Top Clicked Links -->
            @if(!empty($topLinks))
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Top Clicked Links</h3>
                        <div class="space-y-3">
                            @foreach($topLinks as $url => $clicks)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $url }}</p>
                                    </div>
                                    <div class="ml-4 flex-shrink-0">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                                            {{ $clicks }} {{ Str::plural('click', $clicks) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
