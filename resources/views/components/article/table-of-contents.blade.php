@props(['articleId' => 'article-content', 'minHeadings' => 3])

{{-- Table of Contents - Requirement 58 --}}
@php
    // Extract headings from post content for server-side check
    $hasEnoughHeadings = false;
    if (isset($post) && $post->content) {
        preg_match_all('/<h[23][^>]*>.*?<\/h[23]>/i', $post->content, $matches);
        $hasEnoughHeadings = count($matches[0]) >= $minHeadings;
    }
@endphp

@if($hasEnoughHeadings)
<div 
    x-data="tableOfContents({ articleId: '{{ $articleId }}', minHeadings: {{ $minHeadings }} })"
    x-init="init()"
    class="hidden lg:block sticky top-24 self-start bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 max-h-[calc(100vh-8rem)] overflow-y-auto"
    x-cloak
>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide">
            {{ __('post.table_of_contents') }}
        </h3>
        <button 
            @click="expanded = !expanded"
            class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
            :aria-expanded="expanded"
            aria-label="{{ __('post.toggle_table_of_contents') }}"
        >
            <svg 
                x-show="expanded"
                class="w-4 h-4" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
            <svg 
                x-show="!expanded"
                class="w-4 h-4" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </div>

    <nav 
        x-show="expanded"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2"
        aria-label="{{ __('post.table_of_contents') }}"
    >
        <ul class="space-y-1" x-show="headings.length > 0">
            <template x-for="(heading, index) in headings" :key="index">
                <li>
                    <a 
                        :href="`#${heading.id}`"
                        @click.prevent="scrollToSection(heading.id)"
                        class="block px-3 py-2 text-sm rounded-md transition-colors"
                        :class="{
                            'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium border-l-2 border-indigo-600 dark:border-indigo-400': heading.active,
                            'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100': !heading.active
                        }"
                        :style="heading.level === 3 ? 'padding-left: 1.5rem;' : ''"
                        x-text="heading.text"
                    ></a>
                </li>
            </template>
        </ul>
        <p x-show="headings.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('post.no_headings_found') }}
        </p>
    </nav>
</div>
@endif



