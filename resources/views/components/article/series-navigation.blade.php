<div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
	<div class="p-6 border-b border-gray-200 dark:border-gray-700">
		<h3 class="text-xl font-semibold text-gray-900 dark:text-white">
			{{ $series->name }}
		</h3>
		@if(!empty($series->description))
			<p class="mt-2 text-gray-600 dark:text-gray-300">
				{{ $series->description }}
			</p>
		@endif
		@if(isset($navigation['current_position'], $navigation['total_posts']))
			<p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
				{{ __('series.part_of_total', ['current' => $navigation['current_position'], 'total' => $navigation['total_posts']]) }}
			</p>
			<div class="mt-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
				@php
					$percent = $navigation['total_posts'] > 0 ? ($navigation['current_position'] / $navigation['total_posts']) * 100 : 0;
				@endphp
				<div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $percent }}%"></div>
			</div>
		@endif
	</div>
	<div class="p-6">
		<div class="flex items-center justify-between gap-4">
			<div>
				@if($navigation['previous'])
					<a href="{{ route('post.show', $navigation['previous']->slug) }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
						<svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
						</svg>
						{{ __('series.previous_post') }}
					</a>
				@endif
			</div>
			<div>
				@if($navigation['next'])
					<a href="{{ route('post.show', $navigation['next']->slug) }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
						{{ __('series.next_post') }}
						<svg class="h-5 w-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
						</svg>
					</a>
				@endif
			</div>
		</div>
		@if($navigation['all_posts'] && $navigation['all_posts']->count() > 0)
			<ul class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
				@foreach($navigation['all_posts'] as $idx => $p)
					<li class="group">
						<a href="{{ route('post.show', $p->slug) }}" class="block p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-500 bg-gray-50 dark:bg-gray-900/40 hover:bg-white dark:hover:bg-gray-900 transition">
							<div class="flex items-start justify-between">
								<span class="text-xs font-medium text-gray-500 dark:text-gray-400">
									{{ __('series.part_number', ['number' => $idx + 1]) }}
								</span>
								@if(isset($navigation['current_position']) && ($idx + 1) === $navigation['current_position'])
									<span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200">{{ __('series.current') }}</span>
								@endif
							</div>
							<h4 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white line-clamp-2">{{ $p->title }}</h4>
							@if($p->reading_time)
								<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('series.reading_time_minutes', ['minutes' => $p->reading_time]) }}</p>
							@endif
						</a>
					</li>
				@endforeach
			</ul>
		@endif
	</div>
</div>

@props(['series', 'currentPost', 'navigation' => null])

@php
    // If navigation is not provided, calculate it
    if (!$navigation) {
        $posts = $series->posts;
        $currentIndex = $posts->search(fn($p) => $p->id === $currentPost->id);
        
        $navigation = [
            'previous' => $currentIndex > 0 ? $posts[$currentIndex - 1] : null,
            'next' => $currentIndex < $posts->count() - 1 ? $posts[$currentIndex + 1] : null,
            'current_position' => $currentIndex + 1,
            'total_posts' => $posts->count(),
            'all_posts' => $posts
        ];
    }
@endphp

<div 
    class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden"
    x-data="seriesNavigation({{ json_encode([
        'previousUrl' => $navigation['previous'] ? route('post.show', $navigation['previous']->slug) : null,
        'nextUrl' => $navigation['next'] ? route('post.show', $navigation['next']->slug) : null,
        'seriesId' => $series->id,
        'currentPostId' => $currentPost->id,
        'allPostIds' => $navigation['all_posts']->pluck('id')->toArray()
    ]) }})"
    @keydown.window="handleKeyboard($event)"
>
    {{-- Series Header --}}
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2 text-white text-sm font-medium mb-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>Part of a Series</span>
                </div>
                <a 
                    href="{{ route('series.show', $series->slug) }}" 
                    class="text-white text-lg font-bold hover:underline"
                >
                    {{ $series->name }}
                </a>
                @if($series->description)
                    <p class="text-indigo-100 text-sm mt-1">{{ Str::limit($series->description, 100) }}</p>
                @endif
            </div>
            
            {{-- Progress Indicator --}}
            <div class="text-right">
                <div class="text-white text-2xl font-bold">
                    {{ $navigation['current_position'] }}/{{ $navigation['total_posts'] }}
                </div>
                <div class="text-indigo-100 text-xs">
                    {{ round(($navigation['current_position'] / $navigation['total_posts']) * 100) }}% Complete
                </div>
            </div>
        </div>
        
        {{-- Progress Bar --}}
        <div class="mt-3 bg-indigo-400 bg-opacity-30 rounded-full h-2">
            <div 
                class="bg-white rounded-full h-2 transition-all duration-300"
                style="width: {{ ($navigation['current_position'] / $navigation['total_posts']) * 100 }}%"
            ></div>
        </div>
    </div>

    {{-- Navigation Buttons --}}
    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200 dark:divide-gray-700">
        {{-- Previous Article --}}
        <div class="p-6">
            @if($navigation['previous'])
                <a 
                    href="{{ route('post.show', $navigation['previous']->slug) }}" 
                    class="group block"
                >
                    <div class="flex items-start gap-4">
                        @if($navigation['previous']->featured_image_url)
                            <img 
                                src="{{ $navigation['previous']->featured_image_url }}" 
                                alt="{{ $navigation['previous']->image_alt_text ?? $navigation['previous']->title }}"
                                class="w-20 h-20 rounded-lg object-cover flex-shrink-0"
                            >
                        @else
                            <div class="w-20 h-20 rounded-lg bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>
                        @endif
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span>Previous</span>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2">
                                {{ $navigation['previous']->title }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ $navigation['previous']->reading_time }} min read
                            </p>
                        </div>
                    </div>
                </a>
            @else
                <div class="flex items-center justify-center h-full text-gray-400 dark:text-gray-600">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <p class="text-sm">First article in series</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Next Article --}}
        <div class="p-6">
            @if($navigation['next'])
                <a 
                    href="{{ route('post.show', $navigation['next']->slug) }}" 
                    class="group block"
                >
                    <div class="flex items-start gap-4">
                        <div class="flex-1 min-w-0 text-right md:order-1">
                            <div class="flex items-center justify-end gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                                <span>Next</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2">
                                {{ $navigation['next']->title }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ $navigation['next']->reading_time }} min read
                            </p>
                        </div>
                        
                        @if($navigation['next']->featured_image_url)
                            <img 
                                src="{{ $navigation['next']->featured_image_url }}" 
                                alt="{{ $navigation['next']->image_alt_text ?? $navigation['next']->title }}"
                                class="w-20 h-20 rounded-lg object-cover flex-shrink-0 md:order-2"
                            >
                        @else
                            <div class="w-20 h-20 rounded-lg bg-gray-200 dark:bg-gray-700 flex-shrink-0 md:order-2"></div>
                        @endif
                    </div>
                </a>
            @else
                <div class="flex items-center justify-center h-full text-gray-400 dark:text-gray-600">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <p class="text-sm">Last article in series</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- All Articles Dropdown --}}
    <div 
        class="border-t border-gray-200 dark:border-gray-700"
        x-data="{ open: false }"
    >
        <button
            @click="open = !open"
            class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        >
            <span class="font-medium text-gray-900 dark:text-white">
                View all {{ $navigation['total_posts'] }} articles in this series
            </span>
            <svg 
                class="w-5 h-5 text-gray-500 transition-transform"
                :class="{ 'rotate-180': open }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="px-6 pb-6"
            style="display: none;"
        >
            <div class="space-y-2">
                @foreach($navigation['all_posts'] as $index => $post)
                    <a 
                        href="{{ route('post.show', $post->slug) }}" 
                        class="flex items-center gap-3 p-3 rounded-lg transition-colors {{ $post->id === $currentPost->id ? 'bg-indigo-50 dark:bg-indigo-900 border-2 border-indigo-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700 border-2 border-transparent' }}"
                        x-data="{ isRead: isPostRead({{ $post->id }}) }"
                    >
                        <div class="relative flex-shrink-0">
                            <div 
                                class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm transition-colors"
                                :class="isRead ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : '{{ $post->id === $currentPost->id ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}'"
                            >
                                {{ $index + 1 }}
                            </div>
                            <!-- Read checkmark -->
                            <span 
                                x-show="isRead"
                                class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-green-500 text-white"
                            >
                                <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-900 dark:text-white truncate {{ $post->id === $currentPost->id ? 'text-indigo-600 dark:text-indigo-400' : '' }}">
                                {{ $post->title }}
                            </h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $post->reading_time }} min read
                            </p>
                        </div>

                        @if($post->id === $currentPost->id)
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                                    Current
                                </span>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a 
                    href="{{ route('series.show', $series->slug) }}" 
                    class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium text-sm"
                >
                    <span>View series page</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
    function seriesNavigation(config) {
        return {
            previousUrl: config.previousUrl,
            nextUrl: config.nextUrl,
            seriesId: config.seriesId,
            currentPostId: config.currentPostId,
            allPostIds: config.allPostIds,
            readPosts: [],
            
            init() {
                this.loadProgress();
                
                // Auto-mark current post as read after 30 seconds
                setTimeout(() => {
                    this.markCurrentAsRead();
                }, 30000);
                
                // Show keyboard shortcuts hint
                this.showKeyboardHint();
            },
            
            loadProgress() {
                const key = `series_progress_${this.seriesId}`;
                const stored = localStorage.getItem(key);
                this.readPosts = stored ? JSON.parse(stored) : [];
            },
            
            isPostRead(postId) {
                return this.readPosts.includes(postId);
            },
            
            markCurrentAsRead() {
                if (!this.readPosts.includes(this.currentPostId)) {
                    this.readPosts.push(this.currentPostId);
                    const key = `series_progress_${this.seriesId}`;
                    localStorage.setItem(key, JSON.stringify(this.readPosts));
                    
                    // Dispatch event for other components
                    window.dispatchEvent(new CustomEvent('series-progress-updated', {
                        detail: {
                            seriesId: this.seriesId,
                            readPosts: this.readPosts
                        }
                    }));
                }
            },
            
            handleKeyboard(event) {
                // Only handle if not typing in an input
                if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
                    return;
                }
                
                // Alt + Left Arrow = Previous
                if (event.altKey && event.key === 'ArrowLeft' && this.previousUrl) {
                    event.preventDefault();
                    window.location.href = this.previousUrl;
                }
                
                // Alt + Right Arrow = Next
                if (event.altKey && event.key === 'ArrowRight' && this.nextUrl) {
                    event.preventDefault();
                    window.location.href = this.nextUrl;
                }
                
                // Alt + S = View series
                if (event.altKey && event.key === 's') {
                    event.preventDefault();
                    const seriesLink = this.$el.querySelector('a[href*="/series/"]');
                    if (seriesLink) {
                        window.location.href = seriesLink.href;
                    }
                }
            },
            
            showKeyboardHint() {
                // Show hint only once per session
                const hintKey = 'series_keyboard_hint_shown';
                if (!sessionStorage.getItem(hintKey)) {
                    setTimeout(() => {
                        const hint = document.createElement('div');
                        hint.className = 'fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg text-sm z-50 animate-fade-in';
                        hint.innerHTML = `
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <strong>Keyboard Shortcuts</strong>
                            </div>
                            <div class="space-y-1 text-xs">
                                <div><kbd class="px-1 py-0.5 bg-gray-700 rounded">Alt</kbd> + <kbd class="px-1 py-0.5 bg-gray-700 rounded">←</kbd> Previous article</div>
                                <div><kbd class="px-1 py-0.5 bg-gray-700 rounded">Alt</kbd> + <kbd class="px-1 py-0.5 bg-gray-700 rounded">→</kbd> Next article</div>
                                <div><kbd class="px-1 py-0.5 bg-gray-700 rounded">Alt</kbd> + <kbd class="px-1 py-0.5 bg-gray-700 rounded">S</kbd> View series</div>
                            </div>
                        `;
                        document.body.appendChild(hint);
                        
                        setTimeout(() => {
                            hint.style.transition = 'opacity 0.3s';
                            hint.style.opacity = '0';
                            setTimeout(() => hint.remove(), 300);
                        }, 5000);
                        
                        sessionStorage.setItem(hintKey, 'true');
                    }, 2000);
                }
            }
        };
    }
</script>
@endpush
