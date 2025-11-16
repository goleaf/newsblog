@props([
    'post',
    'size' => 'default', // default, large, small
    'showExcerpt' => true,
    'showImage' => true,
    'imageAspect' => 'aspect-video', // aspect-video, aspect-square, aspect-[4/3]
])

@php
    $sizeClasses = [
        'small' => [
            'card' => 'flex flex-col',
            'image' => 'h-40',
            'title' => 'text-base',
            'excerpt' => 'text-sm line-clamp-2',
        ],
        'default' => [
            'card' => 'flex flex-col',
            'image' => 'h-48',
            'title' => 'text-lg',
            'excerpt' => 'text-sm line-clamp-3',
        ],
        'large' => [
            'card' => 'flex flex-col',
            'image' => 'h-64',
            'title' => 'text-xl',
            'excerpt' => 'text-base line-clamp-4',
        ],
    ];
    
    $classes = $sizeClasses[$size] ?? $sizeClasses['default'];
@endphp

<article {{ $attributes->merge(['class' => "group relative bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden {$classes['card']}"]) }}>
    @if($showImage && $post->featured_image_url)
        <a href="{{ route('post.show', $post->slug) }}" class="block overflow-hidden">
            <div class="relative {{ $imageAspect }} {{ $classes['image'] }} overflow-hidden">
                <x-optimized-image 
                    :src="$post->featured_image_url"
                    :alt="$post->image_alt_text ?? $post->title"
                    :width="800"
                    :height="600"
                    sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
                
                {{-- Badges overlay --}}
                <div class="absolute top-3 left-3 flex gap-2">
                    @if($post->is_featured)
                        <x-content.post-badge type="featured" />
                    @endif
                    
                    @if($post->is_trending)
                        <x-content.post-badge type="trending" />
                    @endif

                    @if($post->is_sponsored)
                        <x-content.post-badge type="sponsored" />
                    @endif

                    @if($post->is_editors_pick)
                        <x-content.post-badge type="editors-pick" />
                    @endif
                </div>
                
                {{-- Bookmark Button --}}
                <div class="absolute top-3 right-3 z-10">
                    <x-bookmark-button :post="$post" size="sm" />
                </div>
            </div>
        </a>
    @elseif(!$showImage && ($post->is_featured || $post->is_trending))
        {{-- Show badges when no image --}}
        <div class="p-3 flex gap-2">
            @if($post->is_featured)
                <x-content.post-badge type="featured" />
            @endif
            
            @if($post->is_trending)
                <x-content.post-badge type="trending" />
            @endif

            @if($post->is_sponsored)
                <x-content.post-badge type="sponsored" />
            @endif

            @if($post->is_editors_pick)
                <x-content.post-badge type="editors-pick" />
            @endif
        </div>
    @endif
    
    <div class="flex-1 p-4 flex flex-col">
        {{-- Category Badge --}}
        @if($post->category)
            <div class="mb-2">
                <a 
                    href="{{ route('category.show', $post->category->slug) }}"
                    class="inline-flex items-center"
                >
                    <x-ui.badge 
                        variant="primary" 
                        size="sm"
                        class="hover:opacity-80 transition-opacity"
                    >
                        @if($post->category->icon)
                            <span class="mr-1">{!! $post->category->icon !!}</span>
                        @endif
                        {{ $post->category->name }}
                    </x-ui.badge>
                </a>
            </div>
        @endif
        
        {{-- Title --}}
        <h3 class="mb-2">
            <a 
                href="{{ route('post.show', $post->slug) }}"
                class="font-bold {{ $classes['title'] }} text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 transition-colors line-clamp-2"
            >
                {{ $post->title }}
            </a>
        </h3>
        
        {{-- Excerpt --}}
        @if($showExcerpt && $post->excerpt)
            <p class="mb-3 text-gray-600 dark:text-gray-300 {{ $classes['excerpt'] }} flex-1">
                {{ $post->excerpt }}
            </p>
        @endif
        
        {{-- Meta Information --}}
        <div class="mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
            {{-- Author and Date --}}
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2">
                <div class="flex items-center gap-2">
                    @if($post->user)
                        <a 
                            href="{{ route('profile.edit') }}"
                            class="flex items-center gap-2 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                        >
                            @if($post->user->avatar_url)
                                <x-optimized-image 
                                    :src="$post->user->avatar_url"
                                    :alt="$post->user->name"
                                    :width="24"
                                    :height="24"
                                    :blur-up="false"
                                    class="w-6 h-6 rounded-full object-cover"
                                />
                            @else
                                <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                    <span class="text-xs font-medium">
                                        {{ substr($post->user->name, 0, 1) }}
                                    </span>
                                </div>
                            @endif
                            <span class="font-medium">{{ $post->user->name }}</span>
                        </a>
                    @endif
                </div>
                
                <time 
                    datetime="{{ $post->published_at?->toIso8601String() }}"
                    class="text-xs"
                >
                    {{ $post->formatted_date }}
                </time>
            </div>
            
            {{-- Engagement Metrics --}}
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-3">
                    {{-- Reading Time --}}
                    @if($post->reading_time)
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ $post->reading_time }} min read
                        </span>
                    @endif
                    
                    {{-- View Count --}}
                    @if($post->view_count > 0)
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{ number_format($post->view_count) }}
                        </span>
                    @endif
                </div>
                
                <div class="flex items-center gap-3">
                    {{-- Comments Count --}}
                    @if($post->comments_count > 0)
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            {{ $post->comments_count }}
                        </span>
                    @endif
                    
                    {{-- Reactions Count --}}
                    @if(isset($post->reactions_count) && $post->reactions_count > 0)
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            {{ $post->reactions_count }}
                        </span>
                    @endif
                    
                    {{-- Bookmarks Count --}}
                    @if(isset($post->bookmarks_count) && $post->bookmarks_count > 0)
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                            </svg>
                            {{ $post->bookmarks_count }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    {{-- Bookmark Button (when no image) --}}
    @if(!$showImage || !$post->featured_image_url)
        <div class="absolute top-3 right-3">
            <x-bookmark-button :post="$post" size="sm" />
        </div>
    @endif
</article>
