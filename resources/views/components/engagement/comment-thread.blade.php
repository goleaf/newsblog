@props(['post', 'comments'])

@php
    // Get top-level comments (no parent)
    $topLevelComments = $comments->whereNull('parent_id');
    $totalCount = $comments->count();
@endphp

<div 
    x-data="{
        sortBy: 'newest',
        page: 1,
        perPage: 10,
        replyingTo: null,
        
        get sortedComments() {
            // This would be handled server-side in production
            return {{ $topLevelComments->toJson() }};
        },
        
        toggleReply(commentId) {
            this.replyingTo = this.replyingTo === commentId ? null : commentId;
        }
    }"
    @comment-submitted.window="
        // Refresh comments after submission
        setTimeout(() => window.location.reload(), 1000);
    "
    class="space-y-6"
>
    <!-- Comments Header -->
    <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Comments
            <span class="text-gray-500 dark:text-gray-400">({{ $totalCount }})</span>
        </h2>
        
        <!-- Sort Dropdown -->
        <div class="relative" x-data="{ open: false }">
            <button
                @click="open = !open"
                @click.outside="open = false"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                <span>Sort by: </span>
                <span x-text="sortBy === 'newest' ? 'Newest' : sortBy === 'oldest' ? 'Oldest' : 'Popular'"></span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-10"
                style="display: none;"
            >
                <button
                    @click="sortBy = 'newest'; open = false"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 first:rounded-t-lg"
                    :class="{ 'bg-gray-100 dark:bg-gray-700': sortBy === 'newest' }"
                >
                    Newest First
                </button>
                <button
                    @click="sortBy = 'oldest'; open = false"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                    :class="{ 'bg-gray-100 dark:bg-gray-700': sortBy === 'oldest' }"
                >
                    Oldest First
                </button>
                <button
                    @click="sortBy = 'popular'; open = false"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 last:rounded-b-lg"
                    :class="{ 'bg-gray-100 dark:bg-gray-700': sortBy === 'popular' }"
                >
                    Most Popular
                </button>
            </div>
        </div>
    </div>
    
    <!-- Comment Form (Top Level) -->
    <x-engagement.comment-form :post="$post" />
    
    <!-- Comments List -->
    @if($topLevelComments->isEmpty())
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No comments yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Be the first to share your thoughts!</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($topLevelComments as $comment)
                <x-engagement.comment-item 
                    :comment="$comment" 
                    :post="$post"
                    :level="0"
                />
            @endforeach
        </div>
        
        <!-- Pagination -->
        @if($topLevelComments->count() >= 10)
            <div class="flex justify-center pt-6">
                <button
                    @click="page++"
                    class="px-6 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors"
                >
                    Load More Comments
                </button>
            </div>
        @endif
    @endif
</div>
