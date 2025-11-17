{{--
    Mobile Article Card Component
    
    Optimized article card for mobile devices with touch-friendly interactions.
    Requirements: 17.1, 17.2
--}}

@props([
    'post',
    'showImage' => true,
])

<article class="lg:hidden bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden touch-target-group">
    <a href="{{ route('post.show', $post->slug) }}" class="block">
        @if($showImage && $post->featured_image_url)
            {{-- Mobile-optimized image --}}
            <div class="relative aspect-video overflow-hidden">
                <x-optimized-image 
                    :src="$post->featured_image_url"
                    :alt="$post->image_alt_text ?? $post->title"
                    :width="600"
                    :height="400"
                    sizes="100vw"
                    class="w-full h-full object-cover"
                />
                
                {{-- Badges overlay --}}
                @if($post->is_featured || $post->is_trending || $post->is_editors_pick)
                    <div class="absolute top-2 left-2 flex gap-1.5">
                        @if($post->is_featured)
                            <x-content.post-badge type="featured" size="sm" />
                        @endif
                        @if($post->is_trending)
                            <x-content.post-badge type="trending" size="sm" />
                        @endif
                        @if($post->is_editors_pick)
                            <x-content.post-badge type="editors-pick" size="sm" />
                        @endif
                    </div>
                @endif
            </div>
        @endif
        
        {{-- Content --}}
        <div class="p-4">
            {{-- Category --}}
            @if($post->category)
                <div class="mb-2">
                    <x-ui.badge variant="primary" size="sm">
                        {{ $post->category->name }}
                    </x-ui.badge>
                </div>
            @endif
            
            {{-- Title --}}
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2">
                {{ $post->title }}
            </h3>
            
            {{-- Excerpt --}}
            @if($post->excerpt)
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-3 line-clamp-2">
                    {{ $post->excerpt }}
                </p>
            @endif
            
            {{-- Meta --}}
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-2">
                    @if($post->user && $post->user->avatar_url)
                        <x-optimized-image 
                            :src="$post->user->avatar_url"
                            :alt="$post->user->name"
                            :width="24"
                            :height="24"
                            :blur-up="false"
                            class="w-6 h-6 rounded-full object-cover"
                        />
                    @endif
                    <span class="font-medium">{{ $post->user->name ?? 'Anonymous' }}</span>
                </div>
                
                <div class="flex items-center gap-3">
                    @if($post->reading_time)
                        <span>{{ $post->reading_time }} min</span>
                    @endif
                    @if($post->view_count > 0)
                        <span>{{ number_format($post->view_count) }} views</span>
                    @endif
                </div>
            </div>
        </div>
    </a>
    
    {{-- Action Bar --}}
    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
            @if($post->comments_count > 0)
                <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                    {{ $post->comments_count }}
                </span>
            @endif
        </div>
        
        <x-bookmark-button :post="$post" size="sm" />
    </div>
</article>
