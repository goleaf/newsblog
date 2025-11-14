@props(['post', 'userReaction' => null])

@php
    $reactionTypes = [
        'like' => ['icon' => '👍', 'label' => 'Like', 'color' => 'text-blue-600'],
        'love' => ['icon' => '❤️', 'label' => 'Love', 'color' => 'text-red-600'],
        'laugh' => ['icon' => '😂', 'label' => 'Laugh', 'color' => 'text-yellow-600'],
        'wow' => ['icon' => '😮', 'label' => 'Wow', 'color' => 'text-purple-600'],
        'sad' => ['icon' => '😢', 'label' => 'Sad', 'color' => 'text-gray-600'],
        'angry' => ['icon' => '😠', 'label' => 'Angry', 'color' => 'text-orange-600'],
    ];
    
    $reactionCounts = $post->reactions()
        ->selectRaw('type, COUNT(*) as count')
        ->groupBy('type')
        ->pluck('count', 'type')
        ->toArray();
@endphp

<div 
    x-data="{
        reactions: @js($reactionCounts),
        userReaction: @js($userReaction),
        showPicker: false,
        submitting: false,
        
        async react(type) {
            if (this.submitting) return;
            
            @guest
                window.location.href = '{{ route('login') }}?redirect=' + encodeURIComponent(window.location.href);
                return;
            @endguest
            
            this.submitting = true;
            
            // Optimistic update
            const previousReaction = this.userReaction;
            const previousCounts = { ...this.reactions };
            
            // Remove previous reaction count
            if (previousReaction && this.reactions[previousReaction]) {
                this.reactions[previousReaction]--;
                if (this.reactions[previousReaction] === 0) {
                    delete this.reactions[previousReaction];
                }
            }
            
            // Add new reaction count
            this.userReaction = type;
            this.reactions[type] = (this.reactions[type] || 0) + 1;
            this.showPicker = false;
            
            try {
                const response = await fetch('/api/posts/{{ $post->id }}/reactions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ type })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to react');
                }
                
                const data = await response.json();
                
                // Update with actual count from server
                this.reactions[type] = data.count;
            } catch (error) {
                console.error('Error reacting:', error);
                
                // Rollback on error
                this.reactions = previousCounts;
                this.userReaction = previousReaction;
                
                alert('Failed to add reaction. Please try again.');
            } finally {
                this.submitting = false;
            }
        },
        
        getTotalCount() {
            return Object.values(this.reactions).reduce((sum, count) => sum + count, 0);
        }
    }"
    class="relative"
>
    <!-- Reaction Button -->
    <button
        @click="showPicker = !showPicker"
        @click.outside="showPicker = false"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        :class="{ 'ring-2 ring-blue-500': userReaction }"
        aria-label="React to this article"
        :aria-expanded="showPicker"
    >
        <!-- Current reaction or default icon -->
        <span class="text-xl" x-show="userReaction">
            @foreach($reactionTypes as $type => $config)
                <span x-show="userReaction === '{{ $type }}'">{{ $config['icon'] }}</span>
            @endforeach
        </span>
        <span class="text-xl" x-show="!userReaction">👍</span>
        
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="getTotalCount() || 'React'"></span>
    </button>
    
    <!-- Reaction Picker -->
    <div
        x-show="showPicker"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute bottom-full left-0 mb-2 p-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-10"
        style="display: none;"
    >
        <div class="flex gap-2">
            @foreach($reactionTypes as $type => $config)
                <button
                    @click="react('{{ $type }}')"
                    class="group relative flex flex-col items-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all transform hover:scale-110"
                    :class="{ 'bg-gray-100 dark:bg-gray-700': userReaction === '{{ $type }}' }"
                    :disabled="submitting"
                    aria-label="{{ $config['label'] }}"
                >
                    <span class="text-2xl">{{ $config['icon'] }}</span>
                    
                    <!-- Count badge -->
                    <span 
                        x-show="reactions['{{ $type }}']"
                        class="absolute -top-1 -right-1 px-1.5 py-0.5 text-xs font-bold rounded-full bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900"
                        x-text="reactions['{{ $type }}']"
                    ></span>
                    
                    <!-- Tooltip -->
                    <span class="absolute bottom-full mb-1 px-2 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-700 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                        {{ $config['label'] }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>
    
    <!-- Reaction Summary (shows individual reaction counts) -->
    <div class="flex flex-wrap gap-2 mt-2" x-show="getTotalCount() > 0">
        @foreach($reactionTypes as $type => $config)
            <div 
                x-show="reactions['{{ $type }}']"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-sm"
            >
                <span>{{ $config['icon'] }}</span>
                <span class="font-medium text-gray-700 dark:text-gray-300" x-text="reactions['{{ $type }}']"></span>
            </div>
        @endforeach
    </div>
</div>
