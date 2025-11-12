@extends('layouts.app')

@section('title', $post->meta_title ?? $post->title)
@section('description', $post->getMetaDescription())

@push('meta-tags')
    @php
        $metaTags = $post->getMetaTags();
    @endphp
    
    {{-- Keywords --}}
    @if(!empty($metaTags['keywords']))
        <meta name="keywords" content="{{ $metaTags['keywords'] }}">
    @endif
    
    {{-- Open Graph Meta Tags --}}
    <meta property="og:title" content="{{ $metaTags['og:title'] }}">
    <meta property="og:description" content="{{ $metaTags['og:description'] }}">
    <meta property="og:image" content="{{ $metaTags['og:image'] }}">
    <meta property="og:url" content="{{ $metaTags['og:url'] }}">
    <meta property="og:type" content="{{ $metaTags['og:type'] }}">
    <meta property="og:site_name" content="{{ $metaTags['og:site_name'] }}">
    
    {{-- Open Graph Article Tags --}}
    @if(!empty($metaTags['article:published_time']))
        <meta property="article:published_time" content="{{ $metaTags['article:published_time'] }}">
    @endif
    <meta property="article:modified_time" content="{{ $metaTags['article:modified_time'] }}">
    <meta property="article:author" content="{{ $metaTags['article:author'] }}">
    <meta property="article:section" content="{{ $metaTags['article:section'] }}">
    @foreach($metaTags['article:tag'] as $tag)
        <meta property="article:tag" content="{{ $tag }}">
    @endforeach
    
    {{-- Twitter Card Meta Tags --}}
    <meta name="twitter:card" content="{{ $metaTags['twitter:card'] }}">
    <meta name="twitter:title" content="{{ $metaTags['twitter:title'] }}">
    <meta name="twitter:description" content="{{ $metaTags['twitter:description'] }}">
    <meta name="twitter:image" content="{{ $metaTags['twitter:image'] }}">
    <meta name="twitter:url" content="{{ $metaTags['twitter:url'] }}">
    
    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ $metaTags['og:url'] }}">
@endpush

@push('structured-data')
    {{-- Schema.org Article Structured Data --}}
    <script type="application/ld+json">
        {!! json_encode($post->getStructuredData(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endpush

@section('content')
<!-- Reading Progress Indicator -->
<div x-data="readingProgress()" 
     x-init="init()"
     class="fixed top-0 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 z-50">
    <div class="h-full bg-indigo-600 dark:bg-indigo-500 transition-all duration-100 ease-out"
         :style="`width: ${progress}%`"></div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <article class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden" id="article-content">
        @if($post->featured_image)
            <img src="{{ $post->featured_image_url }}" alt="{{ $post->image_alt_text ?? $post->title }}" class="w-full h-96 object-cover">
        @endif
        <div class="p-8">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                    <span class="text-indigo-600 dark:text-indigo-400 font-medium">{{ $post->category->name }}</span>
                    <span class="mx-2">•</span>
                    <span>{{ $post->user->name }}</span>
                    <span class="mx-2">•</span>
                    <span>{{ $post->formatted_date }}</span>
                    <span class="mx-2">•</span>
                    <span>{{ $post->reading_time_text }}</span>
                    <span class="mx-2">•</span>
                    <span>{{ number_format($post->view_count) }} views</span>
                </div>
                <x-bookmark-button :post="$post" size="lg" />
            </div>

            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">{{ $post->title }}</h1>

            @if($post->excerpt)
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-6">{{ $post->excerpt }}</p>
            @endif

            <div class="prose dark:prose-invert max-w-none mb-8">
                {!! $post->content !!}
            </div>

            @if($post->tags->count() > 0)
                <div class="flex flex-wrap gap-2 mb-8">
                    @foreach($post->tags as $tag)
                        <a href="{{ route('tag.show', $tag->slug) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 hover:bg-indigo-200 dark:bg-indigo-900 dark:text-indigo-200 dark:hover:bg-indigo-800">
                            {{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            <!-- Breadcrumbs -->
            <nav class="mb-6 text-sm" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-gray-500 dark:text-gray-400">
                    <li><a href="{{ route('home') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Home</a></li>
                    <li>/</li>
                    <li><a href="{{ route('category.show', $post->category->slug) }}" class="hover:text-gray-700 dark:hover:text-gray-300">{{ $post->category->name }}</a></li>
                    <li>/</li>
                    <li class="text-gray-900 dark:text-white">{{ $post->title }}</li>
                </ol>
            </nav>

            <!-- Share Buttons -->
            <x-share-buttons :post="$post" />

            <!-- Reactions & Bookmarks -->
            @auth
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mb-6">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Reactions:</span>
                            <button onclick="react('like')" class="px-3 py-1 rounded-full text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300">
                                👍 <span id="like-count">{{ $post->reactions()->where('type', 'like')->count() }}</span>
                            </button>
                            <button onclick="react('love')" class="px-3 py-1 rounded-full text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300">
                                ❤️ <span id="love-count">{{ $post->reactions()->where('type', 'love')->count() }}</span>
                            </button>
                        </div>
                        <button onclick="toggleBookmark()" id="bookmark-btn" class="px-4 py-2 rounded-md text-sm font-medium bg-indigo-100 text-indigo-800 hover:bg-indigo-200 dark:bg-indigo-900 dark:text-indigo-200 dark:hover:bg-indigo-800">
                            <span id="bookmark-text">Bookmark</span>
                        </button>
                    </div>
                </div>
            @endauth

        </div>
    </article>

    <!-- Series Navigation -->
    @if(!empty($seriesData))
        <div class="mt-8 space-y-6">
            @foreach($seriesData as $data)
                <x-series-navigation :series="$data['series']" :navigation="$data['navigation']" />
            @endforeach
        </div>
    @endif

    <!-- Related Posts -->
    @if($relatedPosts->count() > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Related Posts</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($relatedPosts as $relatedPost)
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        @if($relatedPost->featured_image)
                            <img src="{{ $relatedPost->featured_image_url }}" alt="{{ $relatedPost->title }}" class="w-full h-48 object-cover">
                        @endif
                        <div class="p-4">
                            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ $relatedPost->category->name }}</span>
                            <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">
                                <a href="{{ route('post.show', $relatedPost->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">{{ $relatedPost->title }}</a>
                            </h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $relatedPost->excerpt_limited }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Comments Section -->
    <div class="mt-12 bg-white dark:bg-gray-800 rounded-lg shadow p-8" 
         x-data="{ 
             replyingTo: null,
             pageLoadTime: Math.floor(Date.now() / 1000)
         }">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
            Comments ({{ $post->comments->count() }})
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
            <p class="text-gray-500 dark:text-gray-400 mb-8">No comments yet. Be the first to comment!</p>
        @endif

        <!-- Main Comment Form -->
        <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Leave a Comment</h3>
            <form method="POST" action="{{ route('comments.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="post_id" value="{{ $post->id }}">
                <!-- Honeypot field - hidden from humans, visible to bots -->
                <input type="text" name="honeypot" value="" style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1" autocomplete="off">
                <input type="hidden" name="page_load_time" :value="pageLoadTime">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input 
                        type="text" 
                        name="author_name" 
                        placeholder="Your name" 
                        required 
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                    <input 
                        type="email" 
                        name="author_email" 
                        placeholder="Your email" 
                        required 
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                </div>
                <textarea 
                    name="content" 
                    rows="4" 
                    placeholder="Your comment" 
                    required 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                ></textarea>
                <button 
                    type="submit" 
                    class="rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                >
                    Submit Comment
                </button>
            </form>
        </div>
    </div>
</div>

<style>
@media print {
    nav, footer, .no-print, [x-data="readingProgress()"] {
        display: none !important;
    }
    article {
        box-shadow: none !important;
        border: none !important;
    }
    .prose {
        max-width: 100% !important;
    }
}
</style>

<script>
// Reading Progress Indicator Component
function readingProgress() {
    return {
        progress: 0,
        
        init() {
            // Calculate progress on scroll
            window.addEventListener('scroll', () => {
                this.calculateProgress();
            });
            
            // Calculate initial progress
            this.calculateProgress();
        },
        
        calculateProgress() {
            const article = document.getElementById('article-content');
            if (!article) return;
            
            const articleTop = article.offsetTop;
            const articleHeight = article.offsetHeight;
            const windowHeight = window.innerHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Calculate how far through the article we've scrolled
            const scrolled = scrollTop - articleTop;
            const total = articleHeight - windowHeight;
            
            // Calculate percentage (0-100)
            if (total > 0) {
                this.progress = Math.min(100, Math.max(0, (scrolled / total) * 100));
            } else {
                this.progress = 0;
            }
        }
    }
}

@auth
// Reactions
function react(type) {
    fetch('/api/v1/posts/{{ $post->id }}/reactions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ type: type })
    })
    .then(response => response.json())
    .then(data => {
        const countEl = document.getElementById(type + '-count');
        if (countEl) {
            countEl.textContent = data.count;
        }
    })
    .catch(error => console.error('Error:', error));
}

// Bookmarks
function toggleBookmark() {
    fetch('/api/v1/posts/{{ $post->id }}/bookmark', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        const bookmarkText = document.getElementById('bookmark-text');
        if (bookmarkText) {
            bookmarkText.textContent = data.bookmarked ? 'Bookmarked' : 'Bookmark';
        }
    })
    .catch(error => console.error('Error:', error));
}
@endauth
</script>
@endsection

