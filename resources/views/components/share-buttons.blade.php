@props(['post', 'title' => null])

@php
    $shareExcerpt = $post->excerpt
        ? \Illuminate\Support\Str::limit($post->excerpt, 100)
        : \Illuminate\Support\Str::limit(strip_tags($post->content), 100);
@endphp

<div 
    x-data="sharePost({
        url: @js(route('post.show', $post->slug)),
        title: @js($post->title),
        text: @js($shareExcerpt),
        copyErrorMessage: @js(__('post.copy_link_error'))
    })" 
    class="border-t border-gray-200 dark:border-gray-700 pt-6"
>
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ $title ?? __('post.share_this_post') }}</h3>
    <div class="flex items-center gap-3">
        <!-- Facebook Share -->
        <button 
            @click="shareOnFacebook"
            class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-200"
            title="Share on Facebook"
            aria-label="Share on Facebook"
        >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
            </svg>
        </button>

        <!-- Twitter Share -->
        <button 
            @click="shareOnTwitter"
            class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-sky-500 hover:bg-sky-600 text-white transition-colors duration-200"
            title="Share on Twitter"
            aria-label="Share on Twitter"
        >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
            </svg>
        </button>

        <!-- Copy Link -->
        <button 
            @click="copyLink"
            class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white transition-colors duration-200"
            title="Copy link"
            aria-label="Copy link to clipboard"
        >
            <svg x-show="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <svg x-show="copied" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </button>

        <!-- Web Share API (if supported) -->
        <button 
            x-show="canShare"
            x-cloak
            @click="nativeShare"
            class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white transition-colors duration-200"
            title="Share"
            aria-label="Share using device share menu"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
            </svg>
        </button>
    </div>

    <!-- Copy confirmation message -->
    <div 
        x-show="copied" 
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform translate-y-1"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-1"
        class="mt-2 text-sm text-green-600 dark:text-green-400 font-medium"
        role="status"
        aria-live="polite"
    >
        {{ __('post.link_copied') }}
    </div>
</div>
