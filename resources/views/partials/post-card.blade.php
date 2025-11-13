@props(['post'])

<article data-post-item class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow duration-300">
    @if($post->featured_image)
        <a href="{{ route('post.show', $post->slug) }}">
            <x-optimized-image 
                :src="$post->featured_image_url" 
                :alt="$post->image_alt_text ?? $post->title" 
                class="w-full h-48 object-cover"
            />
        </a>
    @endif
    
    <div class="p-6">
        <div class="flex items-center gap-2 mb-3">
            <a 
                href="{{ route('category.show', $post->category->slug) }}" 
                class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300"
            >
                {{ $post->category->name }}
            </a>
        </div>
        
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            <a 
                href="{{ route('post.show', $post->slug) }}" 
                class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
            >
                {{ $post->title }}
            </a>
        </h3>
        
        @if($post->excerpt)
            <p class="text-gray-600 dark:text-gray-300 text-sm mb-4 line-clamp-3">
                {{ $post->excerpt }}
            </p>
        @endif
        
        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <span>{{ $post->user->name }}</span>
                <span>•</span>
                <time datetime="{{ $post->published_at->toIso8601String() }}">
                    {{ $post->published_at->format('M d, Y') }}
                </time>
            </div>
            
            @if($post->reading_time)
                <span>{{ $post->reading_time }} min read</span>
            @endif
        </div>
        
        @if($post->tags->count() > 0)
            <div class="flex flex-wrap gap-2 mt-4">
                @foreach($post->tags->take(3) as $tag)
                    <a 
                        href="{{ route('tag.show', $tag->slug) }}" 
                        class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    >
                        #{{ $tag->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</article>
