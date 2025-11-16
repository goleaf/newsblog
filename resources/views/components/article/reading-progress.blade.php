@props(['articleId' => 'article-content'])

{{-- Reading Progress Indicator - Requirement 21 --}}
<div 
    x-data="readingProgress({ articleId: '{{ $articleId }}' })" 
    x-init="init()"
    class="fixed top-0 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 z-50 print:hidden"
    role="progressbar"
    :aria-valuenow="progress"
    aria-valuemin="0"
    aria-valuemax="100"
    :aria-label="`Reading progress: ${progress}%`"
>
    <div 
        class="h-full bg-indigo-600 dark:bg-indigo-500 transition-all duration-100 ease-out"
        :style="`width: ${progress}%`"
    ></div>
</div>
