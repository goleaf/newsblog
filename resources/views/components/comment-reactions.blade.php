@props(['comment', 'showLabels' => false])

@php
    $reactionTypes = [
        'like' => ['icon' => '👍', 'label' => 'Like'],
        'love' => ['icon' => '❤️', 'label' => 'Love'],
        'laugh' => ['icon' => '😄', 'label' => 'Laugh'],
        'wow' => ['icon' => '😮', 'label' => 'Wow'],
        'sad' => ['icon' => '😢', 'label' => 'Sad'],
        'angry' => ['icon' => '😠', 'label' => 'Angry'],
    ];
    
    // Get reaction counts
    $reactionCounts = [];
    foreach ($reactionTypes as $type => $data) {
        $count = $comment->reactions()->where('type', $type)->count();
        if ($count > 0) {
            $reactionCounts[$type] = $count;
        }
    }
    
    // Get user's reaction if authenticated
    $userReaction = null;
    if (auth()->check()) {
        $userReaction = $comment->reactions()
            ->where('user_id', auth()->id())
            ->first()?->type;
    }
@endphp

<div 
    x-data="{
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
            
            try {
                const response = await fetch('/api/v1/comments/{{ $comment->id }}/reactions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Authorization': 'Bearer ' + (localStorage.getItem('api_token') || '')
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
        
        getCount(type) {
            return this.reactions[type] || 0;
        },
        
        hasReactions() {
            return Object.keys(this.reactions).length > 0;
        }
    }"
    class="flex items-center gap-2 mt-3"
>
    <!-- Reaction Buttons -->
    <div class="flex items-center gap-1">
        @foreach($reactionTypes as $type => $data)
            <button
                @click="react('{{ $type }}')"
                :disabled="loading"
                :class="{
                    'bg-indigo-100 dark:bg-indigo-900 border-indigo-300 dark:border-indigo-700': userReaction === '{{ $type }}',
                    'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700': userReaction !== '{{ $type }}'
                }"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-md border text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                :title="'{{ $data['label'] }}'"
            >
                <span class="text-base">{{ $data['icon'] }}</span>
                <span 
                    x-show="getCount('{{ $type }}') > 0"
                    x-text="getCount('{{ $type }}')"
                    class="text-xs font-medium text-gray-700 dark:text-gray-300"
                ></span>
                @if($showLabels)
                    <span class="hidden sm:inline text-xs text-gray-600 dark:text-gray-400">
                        {{ $data['label'] }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>
    
    <!-- Total Reactions Count (optional) -->
    <div 
        x-show="hasReactions()"
        class="text-xs text-gray-500 dark:text-gray-400 ml-2"
    >
        <span x-text="Object.values(reactions).reduce((a, b) => a + b, 0)"></span>
        <span x-text="Object.values(reactions).reduce((a, b) => a + b, 0) === 1 ? 'reaction' : 'reactions'"></span>
    </div>
</div>
