@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Hero Section -->
    @if($featuredPosts->count() > 0)
        <div class="mb-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    @php $mainPost = $featuredPosts->first(); @endphp
                    <article class="relative h-96 rounded-lg overflow-hidden">
                        @if($mainPost->featured_image)
                            <x-optimized-image 
                                :src="$mainPost->featured_image_url" 
                                :alt="$mainPost->image_alt_text ?? $mainPost->title" 
                                class="w-full h-full object-cover"
                                eager="true"
                            />
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/75 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
                            <span class="text-sm font-medium text-indigo-300">{{ $mainPost->category->name }}</span>
                            <h2 class="mt-2 text-3xl font-bold">
                                <a href="{{ route('post.show', $mainPost->slug) }}" class="hover:underline">{{ $mainPost->title }}</a>
                            </h2>
                            <p class="mt-2 text-sm">{{ $mainPost->excerpt_limited }}</p>
                            <div class="mt-4 flex items-center text-sm">
                                <span>{{ $mainPost->user->name }}</span>
                                <span class="mx-2">•</span>
                                <span>{{ $mainPost->formatted_date }}</span>
                                <span class="mx-2">•</span>
                                <span>{{ $mainPost->reading_time_text }}</span>
                            </div>
                        </div>
                    </article>
                </div>
                <div class="space-y-6">
                    @foreach($featuredPosts->skip(1)->take(2) as $post)
                        <article class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            @if($post->featured_image)
                                <x-optimized-image 
                                    :src="$post->featured_image_url" 
                                    :alt="$post->image_alt_text ?? $post->title" 
                                    class="w-full h-48 object-cover"
                                />
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
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Trending Section -->
    @if($trendingPosts->count() > 0)
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Trending Now</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($trendingPosts as $post)
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        @if($post->featured_image)
                            <x-optimized-image 
                                :src="$post->featured_image_url" 
                                :alt="$post->image_alt_text ?? $post->title" 
                                class="w-full h-48 object-cover"
                            />
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
                                <span>{{ number_format($post->view_count) }} views</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Recent Posts & Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <div class="lg:col-span-3">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Latest Posts</h2>
            <div class="space-y-6">
                @foreach($recentPosts as $post)
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="md:flex">
                            @if($post->featured_image)
                                <div class="md:flex-shrink-0">
                                    <x-optimized-image 
                                        :src="$post->featured_image_url" 
                                        :alt="$post->image_alt_text ?? $post->title" 
                                        class="h-48 w-full md:w-48 object-cover"
                                    />
                                </div>
                            @endif
                            <div class="p-6 flex-1">
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
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Categories</h3>
                <ul class="space-y-2">
                    @foreach($categories as $category)
                        <li>
                            <a href="{{ route('category.show', $category->slug) }}" class="text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 flex justify-between">
                                <span>{{ $category->name }}</span>
                                <span class="text-gray-400 dark:text-gray-500">({{ $category->posts_count }})</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

