{{--
    Breaking News Ticker Component
    
    A horizontal scrolling ticker that displays breaking news posts.
    Auto-rotates every 5 seconds and filters posts from the last 24 hours.
    
    Usage:
    <x-content.breaking-news-ticker :posts="$breakingNews" />
--}}

@props(['posts'])

@php
    // Filter posts from the last 24 hours
    $recentBreakingNews = $posts->filter(function ($post) {
        return $post->published_at && $post->published_at->isAfter(now()->subDay());
    });
@endphp

@if($recentBreakingNews->isNotEmpty())
<div 
    x-data="breakingNewsTicker({{ $recentBreakingNews->count() }})"
    x-init="init()"
    class="bg-gradient-to-r from-red-600 to-red-700 dark:from-red-800 dark:to-red-900 text-white overflow-hidden relative h-12"
    role="region"
    aria-label="Breaking News"
    aria-live="polite"
>
    {{-- Breaking News Label --}}
    <div class="absolute left-0 top-0 bottom-0 z-10 bg-red-600 dark:bg-red-800 px-4 flex items-center shadow-lg">
        <div class="flex items-center gap-2 whitespace-nowrap">
            <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span class="font-bold text-sm uppercase tracking-wider">Breaking</span>
        </div>
    </div>

    {{-- Scrolling Ticker Container --}}
    <div class="overflow-hidden h-full" style="padding-left: 120px;">
        <div 
            class="flex items-center h-full gap-8"
            :style="`transform: translateX(${translateX}px); transition: transform 0.5s ease-in-out;`"
        >
            @foreach($recentBreakingNews as $index => $post)
                <div 
                    class="flex items-center gap-4 whitespace-nowrap flex-shrink-0 h-full"
                    data-index="{{ $index }}"
                >
                    {{-- Separator Dot --}}
                    @if($index > 0)
                        <div class="w-1.5 h-1.5 rounded-full bg-white/60 flex-shrink-0"></div>
                    @endif
                    
                    {{-- Post Link --}}
                    <a 
                        href="{{ route('post.show', $post->slug) }}"
                        @click.prevent="navigateToArticle('{{ route('post.show', $post->slug) }}')"
                        class="flex items-center gap-3 hover:opacity-90 transition-opacity group cursor-pointer"
                        aria-label="Read breaking news: {{ $post->title }}"
                    >
                        <span class="text-sm font-medium line-clamp-1">
                            {{ $post->title }}
                        </span>
                        @if($post->category)
                            <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full flex-shrink-0">
                                {{ $post->category->name }}
                            </span>
                        @endif
                        <time 
                            datetime="{{ $post->published_at->toIso8601String() }}"
                            class="text-xs text-white/80 flex-shrink-0"
                        >
                            {{ $post->published_at->diffForHumans() }}
                        </time>
                    </a>
                </div>
            @endforeach
            
            {{-- Duplicate items for seamless loop --}}
            @foreach($recentBreakingNews as $index => $post)
                <div 
                    class="flex items-center gap-4 whitespace-nowrap flex-shrink-0 h-full"
                    data-index="{{ $index }}"
                >
                    <div class="w-1.5 h-1.5 rounded-full bg-white/60 flex-shrink-0"></div>
                    <a 
                        href="{{ route('post.show', $post->slug) }}"
                        @click.prevent="navigateToArticle('{{ route('post.show', $post->slug) }}')"
                        class="flex items-center gap-3 hover:opacity-90 transition-opacity group cursor-pointer"
                        aria-label="Read breaking news: {{ $post->title }}"
                    >
                        <span class="text-sm font-medium line-clamp-1">
                            {{ $post->title }}
                        </span>
                        @if($post->category)
                            <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full flex-shrink-0">
                                {{ $post->category->name }}
                            </span>
                        @endif
                        <time 
                            datetime="{{ $post->published_at->toIso8601String() }}"
                            class="text-xs text-white/80 flex-shrink-0"
                        >
                            {{ $post->published_at->diffForHumans() }}
                        </time>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('breakingNewsTicker', (totalItems) => ({
        currentIndex: 0,
        translateX: 0,
        itemWidths: [],
        intervalId: null,
        rotationInterval: 5000, // 5 seconds
        isPaused: false,
        
        init() {
            // Calculate item widths after DOM is ready
            this.$nextTick(() => {
                const items = this.$el.querySelectorAll('[data-index]');
                items.forEach((item, index) => {
                    if (index < totalItems) {
                        this.itemWidths.push(item.offsetWidth + 32); // 32px for gap-8
                    }
                });
                
                // Start auto-rotation
                this.startRotation();
            });
            
            // Pause on hover
            this.$el.addEventListener('mouseenter', () => {
                this.pauseRotation();
            });
            
            this.$el.addEventListener('mouseleave', () => {
                if (!this.isPaused) {
                    this.startRotation();
                }
            });
        },
        
        startRotation() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
            }
            
            this.isPaused = false;
            this.intervalId = setInterval(() => {
                this.next();
            }, this.rotationInterval);
        },
        
        pauseRotation() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
            }
            this.isPaused = true;
        },
        
        next() {
            if (this.itemWidths.length === 0) {
                return;
            }
            
            this.currentIndex = (this.currentIndex + 1) % totalItems;
            
            // Calculate cumulative width up to current index
            let cumulativeWidth = 0;
            for (let i = 0; i < this.currentIndex; i++) {
                cumulativeWidth += this.itemWidths[i] || 0;
            }
            
            this.translateX = -cumulativeWidth;
            
            // Reset to beginning for seamless loop
            if (this.currentIndex === 0) {
                setTimeout(() => {
                    this.translateX = 0;
                }, 500);
            }
        },
        
        navigateToArticle(url) {
            window.location.href = url;
        }
    }));
});
</script>
@endif

