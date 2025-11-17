@props(['comment', 'depth' => 0])

<div id="comment-{{ $comment->id }}" class="comment-item" data-comment-id="{{ $comment->id }}" style="margin-left: {{ $depth * 40 }}px;">
    <div class="border-b border-gray-200 dark:border-gray-700 pb-6 last:border-0">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                    <span class="text-indigo-600 dark:text-indigo-300 font-semibold text-sm">
                        {{ strtoupper(substr($comment->author_name, 0, 1)) }}
                    </span>
                </div>
            </div>
            <div class="ml-4 flex-1">
                <div class="flex items-center">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $comment->author_name }}</h4>
                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                    @if($depth > 0)
                        <span class="ml-2 text-xs text-indigo-600 dark:text-indigo-400">
                            Replying to {{ $comment->parent->author_name }}
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-gray-700 dark:text-gray-300">{{ $comment->content }}</p>
                
                <!-- Comment Actions -->
                <div class="mt-3 flex items-center gap-4">
                    <x-engagement.comment-reaction-button :comment="$comment" />
                    
                    @if($comment->canReply())
                        <button 
                            @click="replyingTo = replyingTo === {{ $comment->id }} ? null : {{ $comment->id }}"
                            class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            Reply
                        </button>
                    @endif
                </div>
                
                <!-- Inline Reply Form -->
                <div x-show="replyingTo === {{ $comment->id }}" 
                     x-transition
                     class="mt-4 bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <form method="POST" action="{{ route('comments.store') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="post_id" value="{{ $comment->post_id }}">
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                        <!-- Honeypot field -->
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
                            rows="3" 
                            placeholder="Your reply" 
                            required 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        ></textarea>
                        <div class="flex gap-2">
                            <button 
                                type="submit" 
                                class="rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                            >
                                Submit Reply
                            </button>
                            <button 
                                type="button"
                                @click="replyingTo = null"
                                class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Nested Replies -->
    @if($comment->replies->count() > 0)
        <div class="mt-6 space-y-6">
            @foreach($comment->replies as $reply)
                <x-comment :comment="$reply" :depth="$depth + 1" />
            @endforeach
        </div>
    @endif
</div>
