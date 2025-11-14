@props(['post', 'size' => 'md'])

@php
$isBookmarked = auth()->check() && $post->isBookmarkedBy(auth()->id());
$sizeClasses = [
    'sm' => 'w-4 h-4',
    'md' => 'w-5 h-5',
    'lg' => 'w-6 h-6',
];
$iconSize = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

@auth
<div
    x-data="bookmarkButton({
        toggleUrl: @js(route('bookmarks.toggle', $post)),
        initialBookmarked: @js((bool) $isBookmarked),
        sizeClass: @js($iconSize),
        messages: {
            addToReadingList: @js(__('post.add_to_reading_list')),
            removeFromReadingList: @js(__('post.remove_from_reading_list')),
            error: @js(__('post.bookmark_error')),
        }
    })"
>
    <button
        type="button"
        @click="toggle"
        :title="tooltip"
        :aria-pressed="bookmarked.toString()"
        class="bookmark-button inline-flex items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    >
        <svg
            :class="iconClassList"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            :fill="iconFill"
            stroke="currentColor"
            stroke-width="2"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
        </svg>
    </button>
</div>
@else
<a 
    href="{{ route('login') }}"
    class="inline-flex items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    title="{{ __('post.login_to_bookmark') }}"
>
    <svg class="{{ $iconSize }} stroke-current text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
    </svg>
</a>
@endauth
