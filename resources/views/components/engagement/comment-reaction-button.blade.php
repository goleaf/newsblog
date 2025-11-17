@props(['comment'])

@php
    // Get reaction counts grouped by type
    $reactionCounts = $comment->reactions()
        ->selectRaw('type, COUNT(*) as count')
        ->groupBy('type')
        ->pluck('count', 'type')
        ->toArray();
    
    // Get user's reaction if authenticated
    $userReaction = null;
    if (auth()->check()) {
        $userReaction = $comment->reactions()
            ->where('user_id', auth()->id())
            ->first()?->type;
    }
    
    $totalReactions = array_sum($reactionCounts);
@endphp

<div 
    x-data="{
        open: false,
        reactions: {{ json_encode($reactionCounts) }},
        userReaction: {{ json_encode($userReaction) }},
        loading: false,
        
        async react(type) {
            if (this.loading) return;
            
            @guest
                window.location.href = '{{ route('login') }}';
                return;
            @endguest
            
            this.loading = true;
            this.open = false;
            
            try {
                const response = await fetch('/api/v1/comments/{{ $comment->id }}/reactions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ type })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.reactions = data.counts;
                    this.userReaction = data.user_reaction;
                }
            } catch (error) {
                console.error('Error reacting to comment:', error);
            } finally {
                this.loading = false;
            }
        },
        
        getTotalCount() {
            return Object.values(this.reactions).reduce((a, b) => a + b, 0);
        }
    }"
    class="relative inline-block"
>
    <!-- Reaction Button -->
    <button
        @click="open = !open"
        @click.away="open = false"
        :class="{
            'text-indigo-600 dark:text-indigo-400': userReaction,
            'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': !userReaction
        }"
        class="inline-flex items-center gap-1 text-sm font-medium transition-colors"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
        </svg>
        <span x-show="getTotalCount() > 0" x-text="getTotalCount()"></span>
    </button>
    
    <!-- Reaction Picker Popup -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute bottom-full left-0 mb-2 z-10"
        style="display: none;"
    >
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-2 flex gap-1">
            <button
                @click="react('like')"
                :class="{ 'bg-gray-100 dark:bg-gray-700': userReaction === 'like' }"
                class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Like"
            >
                <span class="text-xl">👍</span>
            </button>
            <button
                @click="react('love')"
                :class="{ 'bg-gray-100 dark:bg-gray-700': userReaction === 'love' }"
                class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Love"
            >
                <span class="text-xl">❤️</span>
            </button>
            <button
                @click="react('laugh')"
                :class="{ 'bg-gray-100 dark:bg-gray-700': userReaction === 'laugh' }"
                class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Laugh"
            >
                <span class="text-xl">😄</span>
            </button>
            <button
                @click="react('wow')"
                :class="{ 'bg-gray-100 dark:bg-gray-700': userReaction === 'wow' }"
                class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Wow"
            >
                <span class="text-xl">😮</span>
            </button>
            <button
                @click="react('sad')"
                :class="{ 'bg-gray-100 dark:bg-gray-700': userReaction === 'sad' }"
                class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Sad"
            >
                <span class="text-xl">😢</span>
            </button>
            <button
                @click="react('angry')"
                :class="{ 'bg-gray-100 dark:bg-gray-700': userReaction === 'angry' }"
                class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                title="Angry"
            >
                <span class="text-xl">😠</span>
            </button>
        </div>
    </div>
</div>
