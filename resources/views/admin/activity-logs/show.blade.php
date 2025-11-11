@extends('admin.layouts.app')

@section('title', 'Activity Log Details')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Activity Log Details</h2>
                    <a href="{{ route('admin.activity-logs.index') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Back to Logs</a>
                </div>
            </div>

            <div class="p-6">
                <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $activityLog->description }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Event</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($activityLog->event ?? 'N/A') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Subject</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $activityLog->subject_type }} #{{ $activityLog->subject_id }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Causer</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            @if($activityLog->causer)
                                {{ $activityLog->causer->name }} ({{ $activityLog->causer->email }})
                            @else
                                System
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $activityLog->ip_address }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User Agent</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $activityLog->user_agent }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $activityLog->created_at->format('Y-m-d H:i:s') }}</dd>
                    </div>
                </dl>

                @if($activityLog->properties)
                    <div class="mt-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Properties</dt>
                        <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-md text-sm overflow-x-auto">{{ json_encode($activityLog->properties, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

