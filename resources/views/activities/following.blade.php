<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Following Activity Feed
            </h2>
            <a href="{{ route('activities.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                View My Activities
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Activity Type Filters -->
            <div class="mb-6">
                <x-activity-filters :current-type="$currentType" :is-following="true" />
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Activity from People You Follow
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Stay updated with what your followed users are doing
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
                            @click="window.location.href = '{{ route('activities.following', ['limit' => 40]) }}'"
                        >
                            Load More Activities
                        </button>
                    </div>
                @else
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No activities yet</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Follow users to see their activities here.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Discover Users
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
