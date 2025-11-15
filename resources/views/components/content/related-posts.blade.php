@props(['posts', 'title' => 'More like this', 'limit' => 5])

@php
    $relatedPosts = $posts->take($limit);
@endphp

@if($relatedPosts->count() > 0)
    <aside class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span>{{ $title }}</span>
        </h2>

        <div class="space-y-4">
            @foreach($relatedPosts as $post)
                <article class="group">
                    <a 
                        href="{{ route('post.show', $post->slug) }}" 
                        class="flex gap-4 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg p-2 -m-2 transition-colors"
                    >
                        {{-- Thumbnail --}}
                        @if($post->featured_image_url)
                            <div class="flex-shrink-0">
                                <x-optimized-image 
                                    :src="$post->featured_image_url" 
                                    :alt="$post->image_alt_text ?? $post->title"
                                    :width="80"
                                    :height="80"
                                    :blur-up="false"
                                    sizes="80px"
                                    class="w-20 h-20 rounded-lg object-cover"
                                />
                            </div>
                        @else
                            <div class="flex-shrink-0 w-20 h-20 rounded-lg bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900 dark:to-purple-900 flex items-center justify-center">
                                <svg class="w-8 h-8 text-indigo-400 dark:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        @endif

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            {{-- Category Badge --}}
                            <span 
                                class="inline-block px-2 py-0.5 rounded text-xs font-medium mb-1"
                                style="background-color: {{ $post->category->color_code ?? '#3b82f6' }}20; color: {{ $post->category->color_code ?? '#3b82f6' }}"
                            >
                                {{ $post->category->name }}
                            </span>

                            {{-- Title --}}
                            <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2 mb-1">
                                {{ $post->title }}
                            </h3>

                            {{-- Meta Info --}}
                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                {{-- Reading Time --}}
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $post->reading_time }} min
                                </span>

                                <span class="text-gray-300 dark:text-gray-600">•</span>

                                {{-- View Count --}}
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    {{ number_format($post->view_count) }}
                                </span>

                                {{-- Trending Badge --}}
                                @if($post->is_trending)
                                    <span class="flex items-center gap-1 text-orange-500">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" />
                                        </svg>
                                        Trending
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                </article>

                @if(!$loop->last)
                    <div class="border-t border-gray-200 dark:border-gray-700"></div>
                @endif
            @endforeach
        </div>

        {{-- View More Link --}}
        @if($posts->count() > $limit)
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a 
                    href="{{ route('home') }}" 
                    class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium text-sm transition-colors"
                >
                    <span>Explore more articles</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        @endif
    </aside>
@endif
