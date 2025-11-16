@props(['posts'])

@if($posts->isNotEmpty())
<div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-600 rounded-lg p-4 mb-8">
    <div class="flex items-center gap-3 mb-4">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <h2 class="text-xl font-bold text-red-900 dark:text-red-100">Breaking News</h2>
        </div>
    </div>
    
    <div class="space-y-3">
        @foreach($posts as $post)
            <div class="flex items-start gap-3 group">
                <div class="flex-shrink-0 w-2 h-2 rounded-full bg-red-600 mt-2"></div>
                <div class="flex-1 min-w-0">
                    <a 
                        href="{{ route('post.show', $post->slug) }}" 
                        class="block group-hover:text-red-700 dark:group-hover:text-red-300 transition-colors"
                    >
                        <h3 class="font-semibold text-gray-900 dark:text-white line-clamp-2">
                            {{ $post->title }}
                        </h3>
                        <div class="flex items-center gap-2 mt-1 text-sm text-gray-600 dark:text-gray-400">
                            <time datetime="{{ $post->published_at->toIso8601String() }}">
                                {{ $post->published_at->diffForHumans() }}
                            </time>
                            @if($post->category)
                                <span>•</span>
                                <a 
                                    href="{{ route('category.show', $post->category->slug) }}"
                                    class="hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                >
                                    {{ $post->category->name }}
                                </a>
                            @endif
                        </div>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

