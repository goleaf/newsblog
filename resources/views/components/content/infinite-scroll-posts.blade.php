@props(['initialPosts', 'nextPageUrl' => null, 'currentPage' => 1])

<div 
    x-data="infiniteScroll('{{ $nextPageUrl }}', {{ $currentPage }})"
    x-init="posts = {{ $initialPosts->toJson() }}"
    class="space-y-6"
>
    <!-- Posts Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="post in posts" :key="post.id">
            <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <!-- Featured Image -->
                <template x-if="post.featured_image">
                    <img 
                        :src="post.featured_image_url" 
                        :alt="post.image_alt_text || post.title"
                        class="w-full h-48 object-cover"
                        loading="lazy"
                    />
                </template>
                
                <div class="p-4">
                    <!-- Category -->
                    <a 
                        :href="`/category/${post.category.slug}`"
                        class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300"
                        x-text="post.category.name"
                    ></a>
                    
                    <!-- Title -->
                    <h3 class="mt-2 text-lg font-bold text-gray-900 dark:text-white line-clamp-2">
                        <a 
                            :href="`/post/${post.slug}`"
                            class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                            x-text="post.title"
                        ></a>
                    </h3>
                    
                    <!-- Excerpt -->
                    <p 
                        class="mt-2 text-sm text-gray-600 dark:text-gray-400 line-clamp-2"
                        x-text="post.excerpt_limited"
                    ></p>
                    
                    <!-- Meta -->
                    <div class="mt-4 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="post.formatted_date"></span>
                        <span>•</span>
                        <span x-text="post.reading_time_text"></span>
                    </div>
                </div>
            </article>
        </template>
    </div>
    
    <!-- Loading Indicator -->
    <div x-show="loading" class="flex justify-center py-8">
        <x-ui.loading-spinner size="lg" />
    </div>
    
    <!-- Error Message -->
    <div x-show="error" class="flex flex-col items-center justify-center py-8">
        <x-ui.error-message 
            x-bind:message="error"
            class="mb-4"
        />
        <button 
            @click="retry()"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
        >
            Try Again
        </button>
    </div>
    
    <!-- End of Results -->
    <div x-show="!hasMore && !loading && posts.length > 0" class="text-center py-8">
        <p class="text-gray-600 dark:text-gray-400">You've reached the end of the articles</p>
    </div>
    
    <!-- Sentinel Element for Intersection Observer -->
    <div x-ref="sentinel" class="h-4"></div>
</div>

<script>
    import infiniteScroll from '@/components/infinite-scroll.js';
    
    document.addEventListener('alpine:init', () => {
        Alpine.data('infiniteScroll', infiniteScroll);
    });
</script>
