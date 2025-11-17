<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Newsletter Dashboard') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.newsletter.preview') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    Preview Newsletter
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Subscribers</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalSubscribers) }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Verification</div>
                        <div class="mt-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($pendingSubscribers) }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Unsubscribed</div>
                        <div class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($unsubscribedCount) }}</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Retention Rate</div>
                        <div class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ $totalSubscribers + $unsubscribedCount > 0 ? round(($totalSubscribers / ($totalSubscribers + $unsubscribedCount)) * 100, 1) : 0 }}%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Frequency Breakdown -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Subscriber Frequency Breakdown</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Daily</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $frequencyBreakdown['daily'] ?? 0 }}</div>
                        </div>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Weekly</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $frequencyBreakdown['weekly'] ?? 0 }}</div>
                        </div>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Monthly</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $frequencyBreakdown['monthly'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manual Send -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Send Newsletter Manually</h3>
                    <form action="{{ route('admin.newsletter.send') }}" method="POST" class="flex gap-4">
                        @csrf
                        <select name="frequency" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="daily">Daily</option>
                            <option value="weekly" selected>Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Send Now
                        </button>
                    </form>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This will send the newsletter to all subscribers with the selected frequency preference.</p>
                </div>
            </div>

            <!-- Recent Sends -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Newsletter Sends</h3>
                        <a href="{{ route('admin.newsletter.subscribers') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                            Manage Subscribers →
                        </a>
                    </div>

                    @if($recentSends->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400">No newsletters sent yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subject</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sent</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Opened</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Clicked</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Failed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($recentSends as $send)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $send['subject'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $send['total_sent'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $send['total_opened'] }}
                                                @if($send['total_sent'] > 0)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ round(($send['total_opened'] / $send['total_sent']) * 100, 1) }}%)</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $send['total_clicked'] }}
                                                @if($send['total_sent'] > 0)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ round(($send['total_clicked'] / $send['total_sent']) * 100, 1) }}%)</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400">{{ $send['total_failed'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $send['created_at']->format('M d, Y H:i') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <a href="{{ route('admin.newsletter.metrics', $send['batch_id']) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
