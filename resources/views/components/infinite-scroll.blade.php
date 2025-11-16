@props(['posts', 'containerClass' => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6', 'cardTemplate' => 'partials.post-card'])

<div 
    x-data="infiniteScroll()"
    x-init="init()"
    data-current-page="{{ $posts->currentPage() }}"
    data-last-page="{{ $posts->lastPage() }}"
    class="infinite-scroll-container"
>
    <!-- Posts Container (Requirement 27.1, 27.2) -->
    <div x-ref="postsContainer" class="{{ $containerClass }}">
        {{ $slot }}
    </div>

    <!-- Loading Spinner (Requirement 27.3) -->
    <div 
        x-show="loading" 
        x-cloak
        class="flex justify-center items-center py-8"
    >
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 dark:border-indigo-400"></div>
    </div>

    <!-- End of Content Message (Requirement 27.5) -->
    <div 
        x-show="finished && !loading" 
        x-cloak
        class="text-center py-8"
    >
        <p class="text-gray-500 dark:text-gray-400 text-lg">{{ __('infinite_scroll.end_of_content') }}</p>
    </div>

    <!-- Error State -->
    <div 
        x-show="error && !loading"
        x-cloak
        class="flex flex-col items-center justify-center py-8 gap-4"
    >
        <p class="text-gray-500 dark:text-gray-400" x-text="window.__ ? window.__(error) : '{{ __('infinite_scroll.error_generic') }}'"></p>
        <button 
            type="button"
            @click="error=null; finished=false; loadMore()"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
        >
            {{ __('Retry') }}
        </button>
    </div>

    <!-- Intersection Observer Sentinel (Requirement 27.1) -->
    <div 
        x-ref="sentinel" 
        x-show="!finished && !loading"
        class="h-1"
    ></div>
</div>
