@extends('layouts.app', ['page' => 'article'])

@push('meta-tags')
    <x-seo.meta-tags :post="$post" />
@endpush

@push('page-scripts')
    <x-page-scripts page="article" />
@endpush

@section('content')
<!-- Reading Progress Indicator -->
<x-article.reading-progress article-id="article-content" />

<!-- Floating Action Bar -->
<x-article.floating-actions :post="$post" />
<div class="fixed bottom-6 right-6 z-40">
	<x-bookmark-button :post="$post" size="lg" />
	</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden" id="article-content" data-post-id="{{ $post->id }}">
                @if($post->featured_image)
                    <x-optimized-image 
                        :src="$post->featured_image_url" 
                        :alt="$post->image_alt_text ?? $post->title"
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
                        <x-article.article-header :post="$post" />
                        <div class="flex gap-2">
                            @if($post->is_sponsored)
                                <x-content.post-badge type="sponsored" />
                            @endif
                            @if($post->is_editors_pick)
                                <x-content.post-badge type="editors-pick" />
                            @endif
                        </div>
                    </div>

                    <!-- Font Size Controls -->
                    <div class="mb-6">
                        <x-article.font-size-controls target="#article-content" />
                    </div>

                    <!-- Article Content -->
                    <x-article.article-content :post="$post" />

                    <!-- Breadcrumbs -->
                    <nav class="mt-8 mb-6 text-sm" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2 text-gray-500 dark:text-gray-400">
                            <li><a href="{{ route('home') }}" class="hover:text-gray-700 dark:hover:text-gray-300">{{ __('breadcrumbs.home') }}</a></li>
                            <li>/</li>
                            <li><a href="{{ route('category.show', $post->category->slug) }}" class="hover:text-gray-700 dark:hover:text-gray-300">{{ $post->category->name }}</a></li>
                            <li>/</li>
                            <li class="text-gray-900 dark:text-white truncate">{{ Str::limit($post->title, 50) }}</li>
                        </ol>
                    </nav>

                    <!-- Share Buttons -->
                    <x-share-buttons :post="$post" :title="__('post.share_this_post')" />
                </div>
            </article>

            <!-- Print Footer (print-only) -->
            <div class="mt-6 print-only">
                <hr style="border: 0; border-top: 1px solid #ccc; margin: 16px 0;">
                <div style="font-size: 12pt; color: #000;">
                    <div><strong>{{ config('app.name') }}</strong></div>
                    <div>{{ url()->current() }}</div>
                    <div>Printed on: {{ now()->format('Y-m-d H:i') }}</div>
                </div>
            </div>

            <!-- Series Navigation -->
            @if(!empty($seriesData))
                <div class="mt-8 space-y-6">
                    @foreach($seriesData as $data)
                        <x-article.series-navigation 
                            :series="$data['series']" 
                            :current-post="$post"
                            :navigation="$data['navigation']" 
                        />
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-1" role="complementary" aria-label="Related content">
            <!-- Related Posts -->
            @if($relatedPosts->count() > 0)
                <x-content.related-posts :posts="$relatedPosts" />
            @endif
        </aside>
    </div>

    <!-- Comments Section -->
    <div class="mt-12 bg-white dark:bg-gray-800 rounded-lg shadow p-8" 
         x-data="{ 
             replyingTo: null,
             pageLoadTime: Math.floor(Date.now() / 1000)
         }">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
            {{ __('post.comments_count', ['count' => $post->comments->count()]) }}
        </h2>

        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900 p-4">
                <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900 p-4">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        @endif

        @if($post->comments->count() > 0)
            <div class="space-y-6 mb-8">
                @foreach($post->comments as $comment)
                    <x-comment :comment="$comment" :depth="0" />
                @endforeach
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 mb-8">{{ __('post.no_comments') }}</p>
        @endif

        <!-- Main Comment Form -->
        <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('post.leave_comment') }}</h3>
            <form method="POST" action="{{ route('comments.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="post_id" value="{{ $post->id }}">
                <!-- Honeypot field - hidden from humans, visible to bots -->
                <input type="text" name="honeypot" value="" style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1" autocomplete="off">
                <input type="hidden" name="page_load_time" :value="pageLoadTime">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label for="author_name" class="sr-only">{{ __('post.your_name') }}</label>
                    <input 
                        type="text" 
                        name="author_name" 
                        id="author_name"
                        placeholder="{{ __('post.your_name') }}" 
                        required 
                        aria-describedby="author_name_hint"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                    <span id="author_name_hint" class="sr-only">{{ __('Your display name for the comment') }}</span>
                    <label for="author_email" class="sr-only">{{ __('post.your_email') }}</label>
                    <input 
                        type="email" 
                        name="author_email" 
                        id="author_email"
                        placeholder="{{ __('post.your_email') }}" 
                        required 
                        aria-describedby="author_email_hint"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                    <span id="author_email_hint" class="sr-only">{{ __('We will not publish your email') }}</span>
                </div>
                <label for="content" class="sr-only">{{ __('post.your_comment') }}</label>
                <textarea 
                    name="content" 
                    id="content"
                    rows="4" 
                    placeholder="{{ __('post.your_comment') }}" 
                    required 
                    aria-describedby="comment_hint"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                ></textarea>
                <span id="comment_hint" class="sr-only">{{ __('Share your thoughts about this article') }}</span>
                <button 
                    type="submit" 
                    class="rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                >
                    {{ __('post.submit_comment') }}
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
