@extends('layouts.app')

@section('title', 'My Reading List')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Reading List</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Posts you've saved for later</p>
    </div>

    @if($bookmarks->count() > 0 || request()->has('category') || request()->has('sort'))
        <!-- Filters and Sort -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <form method="GET" action="{{ route('bookmarks.index') }}" class="flex flex-col sm:flex-row gap-4">
                <!-- Category Filter -->
                <div class="flex-1">
                    <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Filter by Category
                    </label>
                    <select 
                        name="category" 
                        id="category" 
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        onchange="this.form.submit()"
                    >
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
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
                @if(request()->has('category') || request()->has('sort'))
                    <div class="flex items-end">
                        <a href="{{ route('bookmarks.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                            Clear Filters
                        </a>
                    </div>
                @endif
            </form>
        </div>

        @if($bookmarks->count() > 0)
        <!-- Grid Layout -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($bookmarks as $bookmark)
                @php $post = $bookmark->post; @endphp
                <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    @if($post->featured_image)
                        <div class="relative">
                            <img src="{{ $post->featured_image_url }}" alt="{{ $post->image_alt_text ?? $post->title }}" class="w-full h-48 object-cover" loading="lazy">
                            <button 
                                onclick="toggleBookmark({{ $post->id }}, this)"
                                class="absolute top-2 right-2 p-2 bg-white dark:bg-gray-800 rounded-full shadow-lg text-red-500 hover:text-red-600 transition-colors"
                                title="Remove from reading list"
                            >
                                <svg class="w-5 h-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                                </svg>
                            </button>
                        </div>
                    @endif
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: {{ $post->category->color_code ?? '#3B82F6' }}20; color: {{ $post->category->color_code ?? '#3B82F6' }}">
                                {{ $post->category->name }}
                            </span>
                            @if(!$post->featured_image)
                                <button 
                                    onclick="toggleBookmark({{ $post->id }}, this)"
                                    class="text-red-500 hover:text-red-600 transition-colors"
                                    title="Remove from reading list"
                                >
                                    <svg class="w-5 h-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                            <a href="{{ route('post.show', $post->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                {{ $post->title }}
                            </a>
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                            {{ $post->excerpt }}
                        </p>
                        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mb-2">
                            <span>{{ $post->user->name }}</span>
                            <span class="mx-2">•</span>
                            <span>{{ $post->reading_time }} min read</span>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            Saved {{ $bookmark->created_at->diffForHumans() }}
                        </div>
                    </div>
                </article>
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
                    @if(request()->has('category') || request()->has('sort'))
                        Try adjusting your filters to see more results.
                    @else
                        Start building your reading list by bookmarking posts you want to read later.
                    @endif
                </p>
                <div class="mt-6">
                    @if(request()->has('category') || request()->has('sort'))
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
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No bookmarks yet</h3>
            <p class="mt-2 text-gray-500 dark:text-gray-400">Start building your reading list by bookmarking posts you want to read later.</p>
            <div class="mt-6">
                <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Browse Posts
                </a>
            </div>
        </div>
    @endif
</div>

<script>
function toggleBookmark(postId, button) {
    fetch(`/posts/${postId}/bookmark`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the article from the page
            button.closest('article').remove();
            
            // Check if there are no more bookmarks
            const articles = document.querySelectorAll('article');
            if (articles.length === 0) {
                location.reload();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to remove bookmark. Please try again.');
    });
}
</script>
@endsection
