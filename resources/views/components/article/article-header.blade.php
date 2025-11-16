@props(['post'])

<header class="mb-8">
    {{-- Category Badge --}}
    <div class="mb-4">
        <a 
            href="{{ route('category.show', $post->category->slug) }}" 
            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium transition-colors"
            style="background-color: {{ $post->category->color_code ?? '#3b82f6' }}20; color: {{ $post->category->color_code ?? '#3b82f6' }}"
        >
            @if($post->category->icon)
                <span class="mr-1.5" aria-hidden="true">{{ $post->category->icon }}</span>
            @endif
            {{ $post->category->name }}
        </a>
    </div>

    {{-- Title --}}
    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
        {{ $post->title }}
    </h1>

    {{-- Excerpt --}}
    @if($post->excerpt)
        <p class="text-xl text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
            {{ $post->excerpt }}
        </p>
    @endif

    {{-- Author and Meta Information --}}
    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
        {{-- Author Info --}}
        <div class="flex items-center gap-3">
            @if($post->user->avatar_url ?? false)
                <img 
                    src="{{ $post->user->avatar_url }}" 
                    alt="{{ $post->user->name }}"
                    class="w-10 h-10 rounded-full object-cover"
                >
            @else
                <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                    <span class="text-indigo-600 dark:text-indigo-400 font-semibold text-sm">
                        {{ strtoupper(substr($post->user->name, 0, 1)) }}
                    </span>
                </div>
            @endif
            <div>
                <a 
                    href="{{ route('profile.show', $post->user->id) }}" 
                    class="font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                    {{ $post->user->name }}
                </a>
                @if($post->user->bio)
                    <p class="text-xs text-gray-500 dark:text-gray-500">{{ Str::limit($post->user->bio, 50) }}</p>
                @endif
            </div>
        </div>

        {{-- Divider --}}
        <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">•</span>

        {{-- Publish Date --}}
        <time 
            datetime="{{ $post->published_at?->toIso8601String() }}"
            class="flex items-center gap-1.5"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>{{ $post->published_at?->format('M d, Y') ?? $post->created_at->format('M d, Y') }}</span>
        </time>

        {{-- Divider --}}
        <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">•</span>

        {{-- Reading Time --}}
        <span class="flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ $post->reading_time }} min read</span>
        </span>

        {{-- Divider --}}
        <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">•</span>

        {{-- View Count --}}
        <span class="flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <span>{{ number_format($post->view_count) }} views</span>
        </span>

        {{-- Bookmark Button (for logged-in users) --}}
        @auth
            <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">•</span>
            <div x-data="bookmarkButton({
                toggleUrl: @js(route('bookmarks.toggle', $post)),
                initialBookmarked: @js($post->isBookmarkedBy(auth()->id())),
                sizeClass: 'w-4 h-4',
                messages: {
                    addToReadingList: @js(__('post.add_to_reading_list')),
                    removeFromReadingList: @js(__('post.remove_from_reading_list')),
                    error: @js(__('post.bookmark_error')),
                }
            })">
                <button
                    type="button"
                    @click="toggle"
                    :title="tooltip"
                    :aria-pressed="bookmarked.toString()"
                    class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                >
                    <svg
                        :class="iconClassList"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        :fill="iconFill"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                    <span class="text-sm" x-text="bookmarked ? '{{ __('post.bookmarked') }}' : '{{ __('post.bookmark') }}'"></span>
                </button>
            </div>
        @endauth
    </div>

    {{-- Tags --}}
    @if($post->tags->count() > 0)
        <div class="mt-6 flex flex-wrap gap-2">
            @foreach($post->tags as $tag)
                <a 
                    href="{{ route('tag.show', $tag->slug) }}" 
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    {{ $tag->name }}
                </a>
            @endforeach
        </div>
    @endif
</header>
