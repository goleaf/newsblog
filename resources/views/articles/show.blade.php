@extends('layouts.app', ['page' => 'article'])

@push('meta-tags')
    <x-seo.meta-tags :post="$article" />
@endpush

@push('page-scripts')
    <x-page-scripts page="article" />
@endpush

@section('content')
<!-- Reading Progress Indicator -->
<x-article.reading-progress article-id="article-content" />

<!-- Floating Action Bar -->
<x-article.floating-actions :post="$article" />
<div class="fixed bottom-6 right-6 z-40">
    <x-bookmark-button :post="$article" size="lg" />
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden" id="article-content" data-post-id="{{ $article->id }}">
                @if($article->featured_image)
                    <x-optimized-image 
                        :src="$article->featured_image_url" 
                        :alt="$article->image_alt_text ?? $article->title"
                        :width="1200"
                        :height="600"
                        :eager="true"
                        :blur-up="false"
                        sizes="(max-width: 1024px) 100vw, 1200px"
                        class="w-full h-96 object-cover"
                    />
                @endif
                
                <div class="p-8">
                    <!-- Article Header -->
                    <div class="flex items-start justify-between gap-4">
                        <x-article.article-header :post="$article" />
                        <div class="flex gap-2">
                            @if($article->is_sponsored)
                                <x-content.post-badge type="sponsored" />
                            @endif
                            @if($article->is_editors_pick)
                                <x-content.post-badge type="editors-pick" />
                            @endif
                        </div>
                    </div>

                    <!-- Font Size Controls -->
                    <div class="mb-6">
                        <x-article.font-size-controls target="#article-content" />
                    </div>

                    <!-- Article Content -->
                    <x-article.article-content :post="$article" />

                    <!-- Breadcrumbs -->
                    <nav class="mt-8 mb-6 text-sm" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2 text-gray-500 dark:text-gray-400">
                            <li><a href="{{ route('home') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Home</a></li>
                            <li>/</li>
                            <li><a href="{{ route('category.show', $article->category->slug) }}" class="hover:text-gray-700 dark:hover:text-gray-300">{{ $article->category->name }}</a></li>
                            <li>/</li>
                            <li class="text-gray-900 dark:text-white truncate">{{ Str::limit($article->title, 50) }}</li>
                        </ol>
                    </nav>

                    <!-- Share Buttons -->
                    <x-share-buttons :post="$article" title="Share this article" />
                </div>
            </article>

            <!-- Comments Section -->
            @if($article->comments->count() > 0)
                <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Comments ({{ $article->comments->count() }})</h2>
                    @foreach($article->comments as $comment)
                        <x-comment :comment="$comment" />
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Author Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">About the Author</h3>
                <div class="flex items-center gap-4 mb-4">
                    @if($article->author->avatar)
                        <img src="{{ $article->author->avatar }}" alt="{{ $article->author->name }}" class="w-16 h-16 rounded-full">
                    @else
                        <div class="w-16 h-16 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                            <span class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ substr($article->author->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $article->author->name }}</h4>
                        @if($article->author->bio)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($article->author->bio, 100) }}</p>
                        @endif
                    </div>
                </div>
                <a href="{{ route('profile.show', $article->author) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">View Profile</a>
            </div>

            <!-- Tags -->
            @if($article->tags->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tags</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($article->tags as $tag)
                            <a href="{{ route('tag.show', $tag->slug) }}" class="inline-block px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm hover:bg-gray-200 dark:hover:bg-gray-600">
                                {{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
