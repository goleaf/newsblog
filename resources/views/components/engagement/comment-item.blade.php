@props(['comment', 'post', 'level' => 0])

@php
    $maxLevel = 2; // Maximum nesting level (0, 1, 2 = 3 levels total)
    $canReply = $level < $maxLevel;
    $isAuthor = auth()->check() && auth()->id() === $comment->user_id;
    $replies = $comment->replies()->approved()->with('user')->latest()->get();
@endphp

<div 
    x-data="{
        showReplyForm: false,
        editing: false,
        deleting: false,
        
        async deleteComment() {
            if (!confirm('Are you sure you want to delete this comment?')) {
                return;
            }
            
            this.deleting = true;
            
            try {
                const response = await fetch('/comments/{{ $comment->id }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to delete comment');
                }
                
                this.$dispatch('toast', {
                    message: 'Comment deleted successfully',
                    type: 'success'
                });
                
                // Remove comment from DOM
                this.$el.remove();
            } catch (error) {
                console.error('Error deleting comment:', error);
                this.$dispatch('toast', {
                    message: 'Failed to delete comment. Please try again.',
                    type: 'error'
                });
            } finally {
                this.deleting = false;
            }
        }
    }"
    class="flex gap-4"
    :class="{ 'ml-8 md:ml-12': {{ $level }} > 0 }"
>
    <!-- Avatar -->
    <div class="flex-shrink-0">
        @if($comment->user)
            <img 
                src="{{ $comment->user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($comment->user->name) }}" 
                alt="{{ $comment->user->name }}"
                class="w-10 h-10 rounded-full"
            />
        @else
            <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                <span class="text-gray-600 dark:text-gray-300 font-medium text-sm">
                    {{ substr($comment->author_name, 0, 1) }}
                </span>
            </div>
        @endif
    </div>
    
    <!-- Comment Content -->
    <div class="flex-1 min-w-0">
        <!-- Comment Header -->
        <div class="flex items-start justify-between gap-2 mb-2">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                        @if($comment->user)
                            {{ $comment->user->name }}
                        @else
                            {{ $comment->author_name }}
                        @endif
                    </span>
                    
                    @if($comment->user && $comment->user->id === $post->author_id)
                        <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">
                            Author
                        </span>
                    @endif
                    
                    @if($comment->status->value !== 'approved')
                        <span class="px-2 py-0.5 text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded">
                            {{ ucfirst($comment->status->value) }}
                        </span>
                    @endif
                </div>
                
                <time 
                    datetime="{{ $comment->created_at->toIso8601String() }}"
                    class="text-sm text-gray-500 dark:text-gray-400"
                    title="{{ $comment->created_at->format('F j, Y \a\t g:i A') }}"
                >
                    {{ $comment->created_at->diffForHumans() }}
                </time>
            </div>
            
            <!-- Actions Menu -->
            @if($isAuthor)
                <div class="relative" x-data="{ open: false }">
                    <button
                        @click="open = !open"
                        @click.outside="open = false"
                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
                        aria-label="Comment options"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                        </svg>
                    </button>
                    
                    <div
                        x-show="open"
                        x-transition
                        class="absolute right-0 mt-1 w-32 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-10"
                        style="display: none;"
                    >
                        <button
                            @click="editing = true; open = false"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 first:rounded-t-lg"
                        >
                            Edit
                        </button>
                        <button
                            @click="deleteComment(); open = false"
                            :disabled="deleting"
                            class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 last:rounded-b-lg"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Comment Body -->
        <div class="prose dark:prose-invert max-w-none mb-3">
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $comment->content }}</p>
        </div>
        
        <!-- Comment Actions -->
        <div class="flex items-center gap-4">
            @if($canReply)
                <button
                    @click="showReplyForm = !showReplyForm"
                    class="inline-flex items-center gap-1 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                    Reply
                </button>
            @endif
            
            @if($replies->count() > 0)
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $replies->count() }} {{ Str::plural('reply', $replies->count()) }}
                </span>
            @endif
        </div>
        
        <!-- Reply Form -->
        <div x-show="showReplyForm" x-transition class="mt-4" style="display: none;">
            <x-engagement.comment-form :post="$post" :parent-id="$comment->id" />
        </div>
        
        <!-- Nested Replies -->
        @if($replies->count() > 0)
            <div class="mt-4 space-y-4">
                @foreach($replies as $reply)
                    <x-engagement.comment-item 
                        :comment="$reply" 
                        :post="$post"
                        :level="$level + 1"
                    />
                @endforeach
            </div>
        @endif
    </div>
</div>
