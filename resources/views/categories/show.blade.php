@extends('layouts.app')

@section('title', $category->meta_title ?? $category->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white">{{ $category->name }}</h1>
        @if($category->description)
            <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">{{ $category->description }}</p>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($posts as $post)
            <article class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                @if($post->featured_image)
                    <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-full h-48 object-cover">
                @endif
                <div class="p-4">
                    <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ $post->category->name }}</span>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">
                        <a href="{{ route('post.show', $post->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">{{ $post->title }}</a>
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $post->excerpt_limited }}</p>
                    <div class="mt-4 flex items-center text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ $post->formatted_date }}</span>
                        <span class="mx-2">•</span>
                        <span>{{ $post->reading_time_text }}</span>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-3 text-center py-12 text-gray-500 dark:text-gray-400">No posts found in this category.</div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $posts->links() }}
    </div>
</div>
@endsection

