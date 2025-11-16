@props(['categorySections'])

@if($categorySections->isNotEmpty())
    @foreach($categorySections as $category)
        @if($category->posts->isNotEmpty())
            <section class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        @if($category->icon)
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white" 
                                 style="background-color: {{ $category->color_code ?? '#6366f1' }};">
                                <i class="{{ $category->icon }} text-lg"></i>
                            </div>
                        @elseif($category->color_code)
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white" 
                                 style="background-color: {{ $category->color_code }};">
                                <span class="text-lg font-bold">{{ substr($category->name, 0, 1) }}</span>
                            </div>
                        @endif
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                <a 
                                    href="{{ route('category.show', $category->slug) }}"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                                >
                                    {{ $category->name }}
                                </a>
                            </h2>
                            @if($category->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $category->description }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <a 
                        href="{{ route('category.show', $category->slug) }}"
                        class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
                    >
                        View All →
                    </a>
                </div>
                
                <x-content.post-grid :posts="$category->posts" :columns="4" />
            </section>
        @endif
    @endforeach
@endif


