{{--
    Mobile Comment Interface Component
    
    Touch-optimized comment interface for mobile devices.
    Requirements: 17.1, 17.2
--}}

@props([
    'post',
    'comments' => [],
])

<div class="lg:hidden" x-data="mobileComments">
    {{-- Comments Header --}}
    <div class="sticky top-0 z-10 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                Comments ({{ count($comments) }})
            </h3>
            
            <button 
                @click="sortOrder = sortOrder === 'newest' ? 'oldest' : 'newest'"
                type="button"
                class="p-2 min-w-[44px] min-h-[44px] flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white touch-target"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
                <span x-text="sortOrder === 'newest' ? 'Newest' : 'Oldest'"></span>
            </button>
        </div>
    </div>
    
    {{-- Comment Form (Sticky at bottom) --}}
    @auth
        <div class="fixed bottom-0 left-0 right-0 z-20 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 p-4 shadow-lg">
            <form 
                action="{{ route('comments.store', $post) }}" 
                method="POST"
                x-data="{ content: '', submitting: false }"
                @submit="submitting = true"
            >
                @csrf
                
                <div class="flex gap-2">
                    <textarea 
                        name="content"
                        x-model="content"
                        rows="1"
                        placeholder="Add a comment..."
                        class="flex-1 px-4 py-3 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white resize-none"
                        style="min-height: 44px;"
                        @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                        required
                    ></textarea>
                    
                    <button 
                        type="submit"
                        :disabled="!content.trim() || submitting"
                        class="px-4 py-3 min-w-[44px] min-h-[44px] bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors touch-target"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="fixed bottom-0 left-0 right-0 z-20 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 p-4 shadow-lg">
            <a 
                href="{{ route('login') }}"
                class="block w-full py-3 text-center bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors touch-target"
            >
                Sign in to comment
            </a>
        </div>
    @endauth
    
    {{-- Comments List --}}
    <div class="pb-24"> {{-- Extra padding for fixed comment form --}}
        @forelse($comments as $comment)
            <div class="border-b border-gray-200 dark:border-gray-700 p-4">
                {{-- Comment Header --}}
                <div class="flex items-start gap-3 mb-2">
                    @if($comment->user->avatar_url)
                        <x-optimized-image 
                            :src="$comment->user->avatar_url"
                            :alt="$comment->user->name"
                            :width="40"
                            :height="40"
                            :blur-up="false"
                            class="w-10 h-10 rounded-full object-cover flex-shrink-0"
                        />
                    @else
                        <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-medium">
                                {{ substr($comment->user->name, 0, 1) }}
                            </span>
                        </div>
                    @endif
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $comment->user->name }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $comment->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                </div>
                
                {{-- Comment Content --}}
                <div class="ml-13 text-gray-700 dark:text-gray-300 text-base leading-relaxed">
                    {{ $comment->content }}
                </div>
                
                {{-- Comment Actions --}}
                <div class="ml-13 mt-3 flex items-center gap-4">
                    <button 
                        type="button"
                        class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 touch-target"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        Reply
                    </button>
                    
                    @if($comment->reactions_count > 0)
                        <span class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                            {{ $comment->reactions_count }}
                        </span>
                    @endif
                </div>
                
                {{-- Nested Replies --}}
                @if($comment->replies->count() > 0)
                    <div class="ml-13 mt-4 space-y-4">
                        @foreach($comment->replies as $reply)
                            <div class="border-l-2 border-gray-200 dark:border-gray-700 pl-4">
                                <div class="flex items-start gap-2 mb-2">
                                    @if($reply->user->avatar_url)
                                        <x-optimized-image 
                                            :src="$reply->user->avatar_url"
                                            :alt="$reply->user->name"
                                            :width="32"
                                            :height="32"
                                            :blur-up="false"
                                            class="w-8 h-8 rounded-full object-cover"
                                        />
                                    @endif
                                    
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-sm text-gray-900 dark:text-white">
                                                {{ $reply->user->name }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $reply->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                            {{ $reply->content }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="p-8 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400">No comments yet</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Be the first to share your thoughts</p>
            </div>
        @endforelse
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('mobileComments', () => ({
        sortOrder: 'newest',
    }));
});
</script>
