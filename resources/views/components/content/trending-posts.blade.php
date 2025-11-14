@props(['posts', 'limit' => 5])

@php
    $displayPosts = $posts->take($limit);
@endphp

<div class="space-y-4">
    <!-- Section Header -->
    <div class="flex items-center gap-2 mb-6">
        <svg class="w-6 h-6 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
        </svg>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Trending Now</h2>
    </div>
    
    <!-- Mobile: Horizontal Scroll -->
    <div class="lg:hidden overflow-x-auto scrollbar-hide -mx-4 px-4">
        <div class="flex gap-4 pb-4" style="width: max-content;">
            @foreach($displayPosts as $index => $post)
                <article class="w-80 flex-shrink-0 bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <!-- Trending Badge -->
                    <div class="relative">
                        @if($post->featured_image)
                            <x-optimized-image 
                                :src="$post->featured_image_url" 
                                :alt="$post->image_alt_text ?? $post->title" 
                                class="w-full h-48 object-cover"
                            />
                        @else
                            <div class="w-full h-48 bg-gradient-to-br from-indigo-500 to-purple-600"></div>
                        @endif
                        
                        <div class="absolute top-3 left-3">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-orange-500 text-white text-xs font-bold rounded">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
                                </svg>
                                #{{ $index + 1 }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <a 
                            href="{{ route('category.show', $post->category->slug) }}"
                            class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300"
                        >
                            {{ $post->category->name }}
                        </a>
                        
                        <h3 class="mt-2 text-lg font-bold text-gray-900 dark:text-white line-clamp-2">
                            <a 
                                href="{{ route('post.show', $post->slug) }}" 
                                class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                            >
                                {{ $post->title }}
                            </a>
                        </h3>
                        
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                            {{ $post->excerpt_limited }}
                        </p>
                        
                        <div class="mt-4 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                {{ number_format($post->view_count) }}
                            </span>
                            
                            @if(isset($post->reactions_count) && $post->reactions_count > 0)
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                    </svg>
                                    {{ number_format($post->reactions_count) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
    
    <!-- Desktop: Grid Layout -->
    <div class="hidden lg:grid lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($displayPosts as $index => $post)
            <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow group">
                <!-- Trending Badge -->
                <div class="relative">
                    @if($post->featured_image)
                        <x-optimized-image 
                            :src="$post->featured_image_url" 
                            :alt="$post->image_alt_text ?? $post->title" 
                            class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                        />
                    @else
                        <div class="w-full h-48 bg-gradient-to-br from-indigo-500 to-purple-600"></div>
                    @endif
                    
                    <div class="absolute top-3 left-3">
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-orange-500 text-white text-xs font-bold rounded">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
                            </svg>
                            #{{ $index + 1 }}
                        </span>
                    </div>
                </div>
                
                <div class="p-4">
                    <a 
                        href="{{ route('category.show', $post->category->slug) }}"
                        class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300"
                    >
                        {{ $post->category->name }}
                    </a>
                    
                    <h3 class="mt-2 text-lg font-bold text-gray-900 dark:text-white line-clamp-2">
                        <a 
                            href="{{ route('post.show', $post->slug) }}" 
                            class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                        >
                            {{ $post->title }}
                        </a>
                    </h3>
                    
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                        {{ $post->excerpt_limited }}
                    </p>
                    
                    <div class="mt-4 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{ number_format($post->view_count) }}
                        </span>
                        
                        @if(isset($post->reactions_count) && $post->reactions_count > 0)
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                </svg>
                                {{ number_format($post->reactions_count) }}
                            </span>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</div>

<style>
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
