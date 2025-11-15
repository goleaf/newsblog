@props(['post'])

<article class="relative h-[500px] rounded-lg overflow-hidden group">
    @if($post->featured_image)
        <x-optimized-image 
            :src="$post->featured_image_url" 
            :alt="$post->image_alt_text ?? $post->title"
            :width="1600"
            :height="900"
            :eager="true"
            :blur-up="false"
            sizes="100vw"
            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
        />
    @else
        <div class="w-full h-full bg-gradient-to-br from-indigo-500 to-purple-600"></div>
    @endif
    
    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent"></div>
    
    <!-- Content -->
    <div class="absolute inset-0 flex flex-col justify-end p-6 md:p-8 lg:p-12">
        <!-- Category Badge -->
        <div class="mb-4">
            <a 
                href="{{ route('category.show', $post->category->slug) }}"
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors"
            >
                {{ $post->category->name }}
            </a>
        </div>
        
        <!-- Title -->
        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4 line-clamp-3">
            <a 
                href="{{ route('post.show', $post->slug) }}" 
                class="hover:text-indigo-300 transition-colors"
            >
                {{ $post->title }}
            </a>
        </h1>
        
        <!-- Excerpt -->
        <p class="text-lg text-gray-200 mb-6 line-clamp-2 max-w-3xl">
            {{ $post->excerpt_limited }}
        </p>
        
        <!-- Meta Information -->
        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-300 mb-6">
            <!-- Author -->
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold">
                    {{ substr($post->user->name, 0, 1) }}
                </div>
                <span class="font-medium">{{ $post->user->name }}</span>
            </div>
            
            <span class="text-gray-400">•</span>
            
            <!-- Date -->
            <time datetime="{{ $post->published_at->toIso8601String() }}">
                {{ $post->formatted_date }}
            </time>
            
            <span class="text-gray-400">•</span>
            
            <!-- Reading Time -->
            <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $post->reading_time_text }}
            </span>
        </div>
        
        <!-- Call to Action Button -->
        <div>
            <a 
                href="{{ route('post.show', $post->slug) }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-white text-gray-900 font-semibold rounded-lg hover:bg-gray-100 transition-colors"
            >
                Read Article
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
    </div>
</article>
