@props(['categories'])

<div class="space-y-4">
    <!-- Section Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Explore Categories</h2>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Discover articles by topic</p>
    </div>
    
    <!-- Category Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($categories as $category)
            <a 
                href="{{ route('category.show', $category->slug) }}"
                class="group relative bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden"
            >
                <!-- Background Color Accent -->
                @if($category->color_code)
                    <div 
                        class="absolute inset-0 opacity-5 group-hover:opacity-10 transition-opacity"
                        style="background-color: {{ $category->color_code }};"
                    ></div>
                @endif
                
                <div class="relative p-6">
                    <!-- Icon -->
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg mb-4 transition-transform group-hover:scale-110"
                         style="background-color: {{ $category->color_code ?? '#6366f1' }}20;">
                        @if($category->icon)
                            <span class="text-2xl">{{ $category->icon }}</span>
                        @else
                            <svg 
                                class="w-6 h-6" 
                                style="color: {{ $category->color_code ?? '#6366f1' }};"
                                fill="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z" />
                            </svg>
                        @endif
                    </div>
                    
                    <!-- Category Name -->
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                        {{ $category->name }}
                    </h3>
                    
                    <!-- Post Count -->
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>{{ $category->posts_count }} {{ Str::plural('article', $category->posts_count) }}</span>
                    </div>
                    
                    <!-- Description (if available) -->
                    @if($category->description)
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                            {{ $category->description }}
                        </p>
                    @endif
                    
                    <!-- Hover Arrow -->
                    <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
    
    <!-- View All Link -->
    @if($categories->count() > 0)
        <div class="mt-8 text-center">
            <a 
                href="{{ route('category.show', $categories->first()->slug) }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors"
            >
                View All Categories
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
    @endif
</div>
