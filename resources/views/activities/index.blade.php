<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                My Activity Feed
            </h2>
            <a href="{{ route('activities.following') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                View Following Feed
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Activity Type Filters -->
            <div class="mb-6">
                <x-activity-filters :current-type="$currentType" />
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Your Recent Activities
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Track your interactions and contributions
                    </p>
                </div>

                @if($activities->count() > 0)
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($activities as $activity)
                            <x-activity-item :activity="$activity" />
                        @endforeach
                    </div>

                    <!-- Load More -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 text-center">
                        <button 
                            type="button"
                            class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium"
                            x-data
                            @click="window.location.href = '{{ route('activities.index', ['limit' => 40]) }}'"
                        >
                            Load More Activities
                        </button>
                    </div>
                @else
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No activities yet</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Start interacting with content to see your activity here.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Explore Content
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
