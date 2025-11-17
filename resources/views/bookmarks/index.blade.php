@extends('layouts.app')

@section('title', 'My Reading List')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Reading List</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Posts you've saved for later</p>
    </div>

    <!-- Collections Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
        <div class="lg:col-span-1">
            @if($collections->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Collections</h2>
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('bookmarks.index') }}" class="block px-3 py-2 rounded-md {{ !request('collection') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            All Bookmarks
                            <span class="float-right text-sm text-gray-500 dark:text-gray-400">{{ $bookmarks->total() }}</span>
                        </a>
                    </li>
                    @foreach($collections as $collection)
                    <li>
                        <a href="{{ route('bookmarks.collection', $collection) }}" class="block px-3 py-2 rounded-md {{ request('collection') == $collection->id ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            {{ $collection->name }}
                            <span class="float-right text-sm text-gray-500 dark:text-gray-400">{{ $collection->bookmarks_count }}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        
        <div class="lg:col-span-3">
            @if($bookmarks->count() > 0)
                <!-- Filters and Sort -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <form method="GET" action="{{ route('bookmarks.index') }}" class="flex flex-col sm:flex-row gap-4">
                        <!-- Filter by Read Status -->
                        <div class="flex-1">
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Status
                            </label>
                            <select 
                                name="status" 
                                id="status" 
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                onchange="this.form.submit()"
                            >
                                <option value="">All</option>
                                <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Unread</option>
                                <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Read</option>
                            </select>
                        </div>

                        <!-- Sort Options -->
                        <div class="flex-1">
                            <label for="sort" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Sort by
                            </label>
                            <select 
                                name="sort" 
                                id="sort" 
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                onchange="this.form.submit()"
                            >
                                <option value="date_saved" {{ request('sort', 'date_saved') == 'date_saved' ? 'selected' : '' }}>
                                    Date Saved (Newest)
                                </option>
                                <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>
                                    Title (A-Z)
                                </option>
                                <option value="reading_time" {{ request('sort') == 'reading_time' ? 'selected' : '' }}>
                                    Reading Time (Shortest)
                                </option>
                            </select>
                        </div>

                        <!-- Clear Filters -->
                        @if(request()->has('status') || request()->has('sort'))
                            <div class="flex items-end">
                                <a href="{{ route('bookmarks.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                    Clear Filters
                                </a>
                            </div>
                        @endif
                    </form>
                </div>

                <!-- Bookmarks Grid -->
                <div class="space-y-4">
                    @foreach($bookmarks as $bookmark)
                        <x-bookmarks.bookmark-card :bookmark="$bookmark" />
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $bookmarks->links() }}
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No bookmarks found</h3>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">
                        @if(request()->has('status') || request()->has('sort'))
                            Try adjusting your filters to see more results.
                        @else
                            Start building your reading list by bookmarking posts you want to read later.
                        @endif
                    </p>
                    <div class="mt-6">
                        @if(request()->has('status') || request()->has('sort'))
                            <a href="{{ route('bookmarks.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Clear Filters
                            </a>
                        @else
                            <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Browse Posts
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
