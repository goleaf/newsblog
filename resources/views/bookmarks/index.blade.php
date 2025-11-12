@extends('layouts.app')

@section('title', 'My Reading List')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Reading List</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Posts you've saved for later</p>
    </div>

    @if($bookmarks->count() > 0)
        <div class="space-y-6">
            @foreach($bookmarks as $bookmark)
                @php $post = $bookmark->post; @endphp
                <article class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="md:flex">
                        @if($post->featured_image)
                            <div class="md:flex-shrink-0">
                                <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="h-48 w-full md:w-48 object-cover">
                            </div>
                        @endif
                        <div class="p-6 flex-1">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ $post->category->name }}</span>
                                    <h3 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">
                                        <a href="{{ route('post.show', $post->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">{{ $post->title }}</a>
                                    </h3>
                                    <p class="mt-2 text-gray-500 dark:text-gray-400">{{ $post->excerpt_limited }}</p>
                                    <div class="mt-4 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <span>{{ $post->user->name }}</span>
                                        <span class="mx-2">•</span>
                                        <span>{{ $post->formatted_date }}</span>
                                        <span class="mx-2">•</span>
                                        <span>{{ $post->reading_time_text }}</span>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                                        Bookmarked {{ $bookmark->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                <button 
                                    onclick="toggleBookmark({{ $post->id }}, this)"
                                    class="ml-4 text-red-500 hover:text-red-600 transition-colors"
                                    title="Remove from reading list"
                                >
                                    <svg class="w-6 h-6 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                                    </svg>
                                </button>
                            </div>
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
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No bookmarks yet</h3>
            <p class="mt-2 text-gray-500 dark:text-gray-400">Start building your reading list by bookmarking posts you want to read later.</p>
            <div class="mt-6">
                <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
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
