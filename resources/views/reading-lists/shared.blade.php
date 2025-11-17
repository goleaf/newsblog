@extends('layouts.app')

@section('title', $collection->name . ' - Shared Reading List')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-2">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
            <span class="text-sm font-medium text-blue-600 dark:text-blue-400">Shared Reading List</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $collection->name }}</h1>
        @if($collection->description)
            <p class="text-gray-600 dark:text-gray-400 mb-2">{{ $collection->description }}</p>
        @endif
        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
            <span>
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                By {{ $collection->user->name }}
            </span>
            <span>
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                {{ $collection->bookmarks->count() }} {{ Str::plural('article', $collection->bookmarks->count()) }}
            </span>
        </div>
    </div>

    @if($collection->bookmarks->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($collection->bookmarks as $bookmark)
                @php $post = $bookmark->post; @endphp
                <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    @if($post->featured_image)
                        <a href="{{ route('post.show', $post->slug) }}">
                            <img 
                                src="{{ $post->featured_image_url }}" 
                                alt="{{ $post->image_alt_text ?? $post->title }}" 
                                class="w-full h-48 object-cover" 
                                loading="lazy"
                            >
                        </a>
                    @endif
                    <div class="p-6">
                        <div class="mb-2">
                            <span 
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                style="background-color: {{ $post->category->color_code ?? '#3B82F6' }}20; color: {{ $post->category->color_code ?? '#3B82F6' }}"
                            >
                                {{ $post->category->name }}
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                            <a href="{{ route('post.show', $post->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                {{ $post->title }}
                            </a>
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                            {{ $post->excerpt }}
                        </p>
                        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $post->user->name }}</span>
                            <span class="mx-2">•</span>
                            <span>{{ $post->reading_time }} min read</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">This reading list is empty</h3>
            <p class="mt-2 text-gray-500 dark:text-gray-400">
                No articles have been added to this list yet.
            </p>
        </div>
    @endif

    <!-- Call to Action -->
    <div class="mt-12 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-8 text-center">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Create Your Own Reading Lists</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-4">
            Organize and share your favorite articles with custom reading lists
        </p>
        @auth
            <a href="{{ route('reading-lists.index') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors">
                Go to My Reading Lists
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        @else
            <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors">
                Sign Up to Get Started
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        @endauth
    </div>
</div>
@endsection
