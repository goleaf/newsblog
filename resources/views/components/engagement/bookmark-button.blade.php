@props(['post', 'isBookmarked' => false, 'showCount' => true])

<div 
    x-data="{
        bookmarked: @js($isBookmarked),
        count: {{ $post->bookmarks_count ?? 0 }},
        submitting: false,
        
        async toggle() {
            if (this.submitting) return;
            
            @guest
                window.location.href = '{{ route('login') }}?redirect=' + encodeURIComponent(window.location.href);
                return;
            @endguest
            
            this.submitting = true;
            
            // Optimistic update
            const previousBookmarked = this.bookmarked;
            const previousCount = this.count;
            
            this.bookmarked = !this.bookmarked;
            this.count = this.bookmarked ? this.count + 1 : Math.max(0, this.count - 1);
            
            try {
                const response = await fetch('/api/posts/{{ $post->id }}/bookmark', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to bookmark');
                }
                
                const data = await response.json();
                
                // Update with actual state from server
                this.bookmarked = data.bookmarked;
                
                // Show success message
                this.$dispatch('toast', {
                    message: this.bookmarked ? 'Article bookmarked!' : 'Bookmark removed',
                    type: 'success'
                });
            } catch (error) {
                console.error('Error bookmarking:', error);
                
                // Rollback on error
                this.bookmarked = previousBookmarked;
                this.count = previousCount;
                
                this.$dispatch('toast', {
                    message: 'Failed to bookmark. Please try again.',
                    type: 'error'
                });
            } finally {
                this.submitting = false;
            }
        }
    }"
    class="inline-block"
>
    <button
        @click="toggle"
        :disabled="submitting"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border transition-all duration-200"
        :class="{
            'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400': bookmarked,
            'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700': !bookmarked,
            'opacity-50 cursor-not-allowed': submitting
        }"
        :aria-label="bookmarked ? 'Remove bookmark' : 'Bookmark this article'"
        :aria-pressed="bookmarked"
    >
        <!-- Bookmark Icon -->
        <svg 
            class="w-5 h-5 transition-transform duration-200"
            :class="{ 'scale-110': bookmarked }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
        >
            <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
                :fill="bookmarked ? 'currentColor' : 'none'"
            />
        </svg>
        
        <!-- Text and Count -->
        <span class="text-sm font-medium">
            <span x-show="bookmarked">Bookmarked</span>
            <span x-show="!bookmarked">Bookmark</span>
        </span>
        
        @if($showCount)
            <span 
                x-show="count > 0"
                class="px-2 py-0.5 text-xs font-bold rounded-full"
                :class="{
                    'bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300': bookmarked,
                    'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300': !bookmarked
                }"
                x-text="count"
            ></span>
        @endif
    </button>
    
    <!-- Animated checkmark on bookmark -->
    <div 
        x-show="bookmarked"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-0"
        x-transition:enter-end="opacity-100 scale-100"
        class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center"
        style="display: none;"
    >
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
        </svg>
    </div>
</div>
