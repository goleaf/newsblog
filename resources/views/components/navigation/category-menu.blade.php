@props([
    'mobile' => false,
    'limit' => null,
])

@php
    // Fetch active categories with post counts
    $categories = \App\Models\Category::active()
        ->parent()
        ->ordered()
        ->withCount(['posts' => function ($query) {
            $query->published();
        }])
        ->when($limit, fn($query) => $query->limit($limit))
        ->get();
@endphp

@if($mobile)
    {{-- Mobile: Vertical List --}}
    <div class="space-y-1">
        @forelse($categories as $category)
            <a 
                href="{{ route('category.show', $category->slug) }}" 
                class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
            >
                <span class="flex items-center">
                    @if($category->icon)
                        <span class="mr-2 text-lg" style="color: {{ $category->color_code ?? '#6B7280' }}">
                            {!! $category->icon !!}
                        </span>
                    @endif
                    {{ $category->name }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $category->posts_count }}
                </span>
            </a>
        @empty
            <p class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                No categories available
            </p>
        @endforelse
    </div>
@else
    {{-- Desktop: Horizontal Scroll with Mega Menu --}}
    <div 
        x-data="{ 
            openCategory: null,
            scrollLeft: 0,
            canScrollLeft: false,
            canScrollRight: false,
            checkScroll() {
                const container = this.$refs.scrollContainer;
                this.scrollLeft = container.scrollLeft;
                this.canScrollLeft = container.scrollLeft > 0;
                this.canScrollRight = container.scrollLeft < (container.scrollWidth - container.clientWidth);
            }
        }"
        x-init="
            $nextTick(() => {
                checkScroll();
                $refs.scrollContainer.addEventListener('scroll', () => checkScroll());
                window.addEventListener('resize', () => checkScroll());
            });
        "
        class="relative"
    >
        {{-- Scroll Left Button --}}
        <button 
            x-show="canScrollLeft"
            @click="$refs.scrollContainer.scrollBy({ left: -200, behavior: 'smooth' })"
            class="absolute left-0 top-1/2 -translate-y-1/2 z-10 p-2 bg-white dark:bg-gray-800 shadow-lg rounded-full text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            aria-label="Scroll categories left"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        {{-- Category List --}}
        <div 
            x-ref="scrollContainer"
            class="flex items-center space-x-2 overflow-x-auto scrollbar-hide py-2 px-8"
            style="scrollbar-width: none; -ms-overflow-style: none;"
        >
            @forelse($categories as $category)
                <div 
                    @mouseenter="openCategory = {{ $category->id }}"
                    @mouseleave="openCategory = null"
                    class="relative flex-shrink-0"
                >
                    {{-- Category Button --}}
                    <a 
                        href="{{ route('category.show', $category->slug) }}"
                        class="flex items-center space-x-2 px-4 py-2 rounded-full text-sm font-medium transition-colors whitespace-nowrap
                               {{ request()->route('category')?->slug === $category->slug 
                                  ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' 
                                  : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                        style="border-left: 3px solid {{ $category->color_code ?? '#6B7280' }}"
                    >
                        @if($category->icon)
                            <span class="text-base" style="color: {{ $category->color_code ?? '#6B7280' }}">
                                {!! $category->icon !!}
                            </span>
                        @endif
                        <span>{{ $category->name }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            ({{ $category->posts_count }})
                        </span>
                    </a>

                    {{-- Mega Menu Dropdown (if category has children or description) --}}
                    @if($category->children->isNotEmpty() || $category->description)
                        <div 
                            x-show="openCategory === {{ $category->id }}"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform scale-100"
                            x-transition:leave-end="opacity-0 transform scale-95"
                            class="absolute left-0 top-full mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-4 z-50"
                            @click.away="openCategory = null"
                        >
                            {{-- Category Description --}}
                            @if($category->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    {{ $category->description }}
                                </p>
                            @endif

                            {{-- Subcategories --}}
                            @if($category->children->isNotEmpty())
                                <div class="space-y-2">
                                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Subcategories
                                    </h4>
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach($category->children as $child)
                                            <a 
                                                href="{{ route('category.show', $child->slug) }}"
                                                class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors"
                                            >
                                                @if($child->icon)
                                                    <span style="color: {{ $child->color_code ?? '#6B7280' }}">
                                                        {!! $child->icon !!}
                                                    </span>
                                                @endif
                                                <span>{{ $child->name }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- View All Link --}}
                            <a 
                                href="{{ route('category.show', $category->slug) }}"
                                class="block mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                            >
                                View all in {{ $category->name }} →
                            </a>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 px-4">
                    No categories available
                </p>
            @endforelse
        </div>

        {{-- Scroll Right Button --}}
        <button 
            x-show="canScrollRight"
            @click="$refs.scrollContainer.scrollBy({ left: 200, behavior: 'smooth' })"
            class="absolute right-0 top-1/2 -translate-y-1/2 z-10 p-2 bg-white dark:bg-gray-800 shadow-lg rounded-full text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            aria-label="Scroll categories right"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    {{-- Hide scrollbar CSS --}}
    <style>
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
    </style>
@endif
