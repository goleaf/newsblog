<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Newsletter Send #{{ $send->id }}
            </h2>
            <a href="{{ route('admin.newsletters.sends') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Sends</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Subscriber</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $subscriber?->email ?? '—' }} (ID: {{ $send->subscriber_id }})</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Status</dt>
                            <dd>
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs {{ $send->status === 'sent' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200' : ($send->status === 'queued' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-200') }}">
                                    {{ ucfirst(is_string($send->status) ? $send->status : ($send->status?->value ?? '')) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Subject</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $send->subject }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Sent At</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $send->sent_at ? $send->sent_at->format('M d, Y H:i') : '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Metrics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="p-4 rounded border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Opens</div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $metrics['opens'] }}</div>
                        </div>
                        <div class="p-4 rounded border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Clicks</div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $metrics['clicks'] }}</div>
                        </div>
                        <div class="p-4 rounded border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Last Opened</div>
                            <div class="text-sm text-gray-900 dark:text-white">{{ $metrics['last_opened_at'] ?? '—' }}</div>
                        </div>
                        <div class="p-4 rounded border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Last Clicked</div>
                            <div class="text-sm text-gray-900 dark:text-white">{{ $metrics['last_clicked_at'] ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Content Preview</h3>
                    <div class="prose dark:prose-invert max-w-none border border-gray-200 dark:border-gray-700 rounded p-4 bg-white dark:bg-gray-900">
                        {!! $send->content !!}
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Actions</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Requeue this send for delivery.</p>
                    </div>
                    <form action="{{ route('admin.newsletters.sends.resend', $send) }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Resend
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
