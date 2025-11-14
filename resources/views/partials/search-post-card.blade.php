@props(['post', 'fuzzyEnabled' => false, 'query' => '', 'searchLogId' => null, 'position' => 0])

<article 
    data-post-item 
    data-search-result
    @if($searchLogId)
        data-search-log-id="{{ $searchLogId }}"
        data-post-id="{{ $post->id }}"
        data-position="{{ $position }}"
    @endif
    class="bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden"
>
    <div class="md:flex">
        @if($post->featured_image)
            <div class="md:flex-shrink-0">
                <a href="{{ route('post.show', $post->slug) }}">
                    <img 
                        src="{{ $post->featured_image_url }}" 
                        alt="{{ $post->image_alt_text ?? $post->title }}" 
                        class="h-48 w-full md:w-48 object-cover hover:opacity-90 transition-opacity"
                        loading="lazy"
                    >
                </a>
            </div>
        @endif
        <div class="p-6 flex-1">
            <!-- Category and Relevance Score -->
            <div class="flex items-center gap-3 mb-2 flex-wrap">
                <a 
                    href="{{ route('category.show', $post->category->slug) }}"
                    class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                    {{ $post->category->name }}
                </a>
                
                @if(isset($post->relevance_score) && $fuzzyEnabled)
                    <span class="relevance-score hidden text-xs px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full font-medium">
                        {{ round($post->relevance_score) }}% match
                    </span>
                @endif
                
                @if(isset($post->match_type))
                    <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full">
                        {{ ucfirst($post->match_type) }} match
                    </span>
                @endif
                
                @if(isset($post->is_phonetic) && $post->is_phonetic)
                    <span class="text-xs px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-full" title="Phonetic match - sounds similar">
                        <svg class="inline-block w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                        </svg>
                        Phonetic
                    </span>
                @endif
            </div>
            
            <!-- Title with Highlighting -->
            <h3 class="mt-2 text-xl font-semibold text-gray-900 dark:text-white leading-tight">
                <a href="{{ route('post.show', $post->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    {!! $post->highlighted_title ?? e($post->title) !!}
                </a>
            </h3>
            
            <!-- Context Snippet with Highlighting -->
            <div class="mt-3 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                @if(isset($post->excerpt_context) && !empty($post->excerpt_context))
                    {!! $post->excerpt_context !!}
                @elseif(isset($post->highlighted_excerpt) && !empty($post->highlighted_excerpt))
                    {!! $post->highlighted_excerpt !!}
                @else
                    {{ Str::limit($post->excerpt ?? '', 200) }}
                @endif
            </div>
            
            <!-- Metadata -->
            <div class="mt-4 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-2">
                    <img 
                        src="{{ $post->user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($post->user->name) }}" 
                        alt="{{ $post->user->name }}"
                        class="w-6 h-6 rounded-full"
                    >
                    <span>{{ $post->user->name }}</span>
                </div>
                <span class="text-gray-400 dark:text-gray-600">•</span>
                <time datetime="{{ $post->published_at->toIso8601String() }}">
                    {{ $post->published_at->format('M j, Y') }}
                </time>
                <span class="text-gray-400 dark:text-gray-600">•</span>
                <span>{{ $post->reading_time }} min read</span>
                
                @if($post->view_count > 0)
                    <span class="text-gray-400 dark:text-gray-600">•</span>
                    <span>{{ number_format($post->view_count) }} views</span>
                @endif
            </div>
            
            <!-- Tags -->
            @if($post->tags->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($post->tags->take(3) as $tag)
                        <a 
                            href="{{ route('tag.show', $tag->slug) }}"
                            class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        >
                            #{{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</article>
