@extends('layouts.app')

@section('title', $post->meta_title ?? $post->title)
@section('description', $post->meta_description ?? $post->excerpt)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <article class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        @if($post->featured_image)
            <img src="{{ $post->featured_image_url }}" alt="{{ $post->image_alt_text ?? $post->title }}" class="w-full h-96 object-cover">
        @endif
        <div class="p-8">
            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-4">
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

            <!-- Reading Progress Bar -->
            <div class="fixed top-0 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 z-50">
                <div id="reading-progress" class="h-full bg-indigo-600 transition-all duration-150" style="width: 0%"></div>
            </div>

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
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Share this post</h3>
                <div class="flex space-x-4">
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($post->title) }}" target="_blank" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                        </svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <span class="sr-only">Facebook</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>
            <!-- Share Buttons -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mb-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Share this post</h3>
                <div class="flex space-x-4">
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($post->title) }}" target="_blank" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                        </svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <span class="sr-only">Facebook</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>

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
    <div class="mt-12 bg-white dark:bg-gray-800 rounded-lg shadow p-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Comments</h2>
        @if($post->comments->count() > 0)
            <div class="space-y-6">
                @foreach($post->comments as $comment)
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-6 last:border-0">
                        <div class="flex items-start">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $comment->author_name }}</h4>
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="mt-2 text-gray-700 dark:text-gray-300">{{ $comment->content }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">No comments yet. Be the first to comment!</p>
        @endif

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

        <!-- Comment Form -->
        <div class="mt-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Leave a Comment</h3>
            <form method="POST" action="{{ route('comments.store') }}" class="space-y-4" id="comment-form">
                @csrf
                <input type="hidden" name="post_id" value="{{ $post->id }}">
                <!-- Honeypot field - hidden from humans, visible to bots -->
                <input type="text" name="honeypot" value="" id="honeypot-field" style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1" autocomplete="off">
                <input type="hidden" name="page_load_time" value="" id="page-load-time-field">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="text" name="author_name" placeholder="Your name" required class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <input type="email" name="author_email" placeholder="Your email" required class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <textarea name="content" rows="4" placeholder="Your comment" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                <button type="submit" class="rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">Submit Comment</button>
            </form>
        </div>
    </div>
</div>

<style>
@media print {
    nav, footer, .no-print, #reading-progress {
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
// Track page load time for spam detection
(function() {
    const pageLoadTime = Math.floor(Date.now() / 1000);
    const pageLoadTimeField = document.getElementById('page-load-time-field');
    if (pageLoadTimeField) {
        pageLoadTimeField.value = pageLoadTime;
    }
})();

// Reading progress bar
window.addEventListener('scroll', function() {
    const article = document.querySelector('article');
    if (!article) return;
    
    const articleTop = article.offsetTop;
    const articleHeight = article.offsetHeight;
    const windowHeight = window.innerHeight;
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    const progress = Math.min(100, ((scrollTop - articleTop + windowHeight) / articleHeight) * 100);
    const progressBar = document.getElementById('reading-progress');
    if (progressBar) {
        progressBar.style.width = Math.max(0, progress) + '%';
    }
});

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

