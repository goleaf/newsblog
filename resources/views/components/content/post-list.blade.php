@props([
    'posts',
    'gap' => 4, // Tailwind gap value
    'showImage' => true,
    'imageSize' => 'default', // small, default, large
])

@php
    $imageSizeClasses = [
        'small' => 'w-24 h-24',
        'default' => 'w-32 h-32 sm:w-40 sm:h-40',
        'large' => 'w-40 h-40 sm:w-48 sm:h-48',
    ];
    
    $imageClass = $imageSizeClasses[$imageSize] ?? $imageSizeClasses['default'];
    $gapClass = "gap-{$gap}";
@endphp

<div {{ $attributes->merge(['class' => "flex flex-col {$gapClass}"]) }}>
    @forelse($posts as $post)
        <article class="group bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden">
            <div class="flex flex-col sm:flex-row">
                {{-- Thumbnail --}}
                @if($showImage && $post->featured_image_url)
                    <a href="{{ route('post.show', $post->slug) }}" class="flex-shrink-0">
                        <div class="relative {{ $imageClass }} overflow-hidden">
                            <x-optimized-image 
                                :src="$post->featured_image_url"
                                :alt="$post->image_alt_text ?? $post->title"
                                :width="200"
                                :height="200"
                                sizes="(max-width: 640px) 96px, 160px"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                            
                            {{-- Badges overlay --}}
                            <div class="absolute top-2 left-2 flex gap-1">
                                @if($post->is_featured)
                                    <x-content.post-badge type="featured" />
                                @endif
                                
                                @if($post->is_trending)
                                    <x-content.post-badge type="trending" />
                                @endif
                            </div>
                        </div>
                    </a>
                @endif
                
                {{-- Content --}}
                <div class="flex-1 p-4 flex flex-col min-w-0">
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
                            class="font-bold text-lg text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 transition-colors line-clamp-2"
                        >
                            {{ $post->title }}
                        </a>
                    </h3>
                    
                    {{-- Excerpt --}}
                    @if($post->excerpt)
                        <p class="mb-3 text-sm text-gray-600 dark:text-gray-300 line-clamp-2 flex-1">
                            {{ $post->excerpt }}
                        </p>
                    @endif
                    
                    {{-- Meta Information --}}
                    <div class="mt-auto">
                        {{-- Author and Date --}}
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
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
                            
                            <time 
                                datetime="{{ $post->published_at?->toIso8601String() }}"
                                class="text-xs"
                            >
                                {{ $post->formatted_date }}
                            </time>
                        </div>
                        
                        {{-- Engagement Metrics --}}
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                            {{-- Reading Time --}}
                            @if($post->reading_time)
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $post->reading_time }} min
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
        </article>
    @empty
        <x-ui.empty-state
            title="No posts found"
            description="There are no posts to display at the moment."
        >
            <x-slot:icon>
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </x-slot:icon>
        </x-ui.empty-state>
    @endforelse
</div>
