@extends('layouts.app')

@section('title', 'Search Results')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-8">Search Results for "{{ $query }}"</h1>

    <div class="space-y-6">
        @forelse($posts as $post)
            <article class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="md:flex">
                    @if($post->featured_image)
                        <div class="md:flex-shrink-0">
                            <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="h-48 w-full md:w-48 object-cover">
                        </div>
                    @endif
                    <div class="p-6 flex-1">
                        <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ $post->category->name }}</span>
                        <h3 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">
                            <a href="{{ route('post.show', $post->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">{{ $post->title }}</a>
                        </h3>
                        <p class="mt-2 text-gray-500 dark:text-gray-400">{{ $post->excerpt_limited }}</p>
                        <div class="mt-4 flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <span>{{ $post->formatted_date }}</span>
                            <span class="mx-2">•</span>
                            <span>{{ $post->reading_time_text }}</span>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">No posts found matching your search.</div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $posts->links() }}
    </div>
</div>
@endsection

