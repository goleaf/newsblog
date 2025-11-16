@props([
    'mobile' => false,
    'limit' => null,
])

{{-- Categories are provided by CategoryMenuComposer --}}

@if($mobile)
    {{-- Mobile: Vertical List --}}
    <nav aria-label="{{ __('Category navigation') }}" class="space-y-1">
        @forelse($categories as $category)
            <a 
                href="{{ route('category.show', $category->slug) }}" 
                class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            >
                <span class="flex items-center">
                    @if($category->icon)
                        <span class="mr-2 text-lg" style="color: {{ $category->color_code ?? '#6B7280' }}">
                            {!! $category->icon !!}
                        </span>
                    @endif
                    {{ $category->name }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400" aria-label="{{ __(':count posts', ['count' => $category->posts_count]) }}">
                    {{ $category->posts_count }}
                </span>
            </a>
        @empty
            <p class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('No categories available') }}
            </p>
        @endforelse
    </nav>
@else
    {{-- Desktop: Horizontal Scroll with Mega Menu --}}
    <nav aria-label="{{ __('Category navigation') }}" 
        x-data="{ 
            openCategory: null,
            scrollLeft: 0,
            canScrollLeft: false,
            canScrollRight: false,
            scrollHandler: null,
            resizeHandler: null,
            checkScroll() {
                const container = this.$refs.scrollContainer;
                if (!container) return;
                this.scrollLeft = container.scrollLeft;
                this.canScrollLeft = container.scrollLeft > 0;
                this.canScrollRight = container.scrollLeft < (container.scrollWidth - container.clientWidth);
            },
            init() {
                this.$nextTick(() => {
                    this.checkScroll();
                    this.scrollHandler = () => this.checkScroll();
                    this.resizeHandler = () => this.checkScroll();
                    this.$refs.scrollContainer.addEventListener('scroll', this.scrollHandler);
                    window.addEventListener('resize', this.resizeHandler);
                });
            },
            destroy() {
                if (this.scrollHandler) {
                    this.$refs.scrollContainer?.removeEventListener('scroll', this.scrollHandler);
                }
                if (this.resizeHandler) {
                    window.removeEventListener('resize', this.resizeHandler);
                }
            }
        }"
        x-init="init()"
        @destroy="destroy()"
        class="relative"
    >
        {{-- Scroll Left Button --}}
        <button 
            x-show="canScrollLeft"
            @click="$refs.scrollContainer.scrollBy({ left: -200, behavior: 'smooth' })"
            class="absolute left-0 top-1/2 -translate-y-1/2 z-10 p-2 bg-white dark:bg-gray-800 shadow-lg rounded-full text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            aria-label="{{ __('Scroll categories left') }}"
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
                    @keydown.escape="openCategory = null"
                    class="relative flex-shrink-0"
                >
                    {{-- Category Button --}}
                    <a 
                        href="{{ route('category.show', $category->slug) }}"
                        class="flex items-center space-x-2 px-4 py-2 rounded-full text-sm font-medium transition-colors whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900
                               {{ request()->route('category')?->slug === $category->slug 
                                  ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' 
                                  : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                        style="border-left: 3px solid {{ $category->color_code ?? '#6B7280' }}"
                        @if($category->children->isNotEmpty() || $category->description || $category->posts->isNotEmpty())
                            aria-haspopup="true"
                            :aria-expanded="openCategory === {{ $category->id }} ? 'true' : 'false'"
                            aria-controls="mega-menu-{{ $category->id }}"
                            @focus="openCategory = {{ $category->id }}"
                            @blur="setTimeout(() => { if (!$el.parentElement.contains(document.activeElement)) openCategory = null }, 100)"
                        @endif
                    >
                        @if($category->icon)
                            <span class="text-base" style="color: {{ $category->color_code ?? '#6B7280' }}">
                                {!! $category->icon !!}
                            </span>
                        @endif
                        <span>{{ $category->name }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400" aria-label="{{ __(':count posts', ['count' => $category->posts_count]) }}">
                            ({{ $category->posts_count }})
                        </span>
                    </a>

                    {{-- Mega Menu Dropdown (if category has children, description, or posts) --}}
                    @if($category->children->isNotEmpty() || $category->description || $category->posts->isNotEmpty())
                        <div 
                            id="mega-menu-{{ $category->id }}"
                            x-show="openCategory === {{ $category->id }}"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform scale-100"
                            x-transition:leave-end="opacity-0 transform scale-95"
                            class="absolute left-0 top-full mt-2 {{ $category->children->isNotEmpty() && $category->posts->isNotEmpty() ? 'w-[600px]' : 'w-80' }} bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-6 z-50"
                            role="region"
                            aria-label="{{ $category->name }} menu"
                            @click.away="openCategory = null"
                        >
                            <div class="grid {{ $category->children->isNotEmpty() && $category->posts->isNotEmpty() ? 'grid-cols-2' : 'grid-cols-1' }} gap-6">
                                {{-- Left Column: Description and Subcategories --}}
                                <div>
                                    {{-- Category Description --}}
                                    @if($category->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                            {{ $category->description }}
                                        </p>
                                    @endif

                                    {{-- Subcategories --}}
                                    @if($category->children->isNotEmpty())
                                        <div class="space-y-2">
                                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                                                {{ __('Subcategories') }}
                                            </h4>
                                            <div class="space-y-1">
                                                @foreach($category->children as $child)
                                                    <a 
                                                        href="{{ route('category.show', $child->slug) }}"
                                                        class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors group focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset"
                                                    >
                                                        <span class="flex items-center space-x-2">
                                                            @if($child->icon)
                                                                <span class="text-base" style="color: {{ $child->color_code ?? '#6B7280' }}">
                                                                    {!! $child->icon !!}
                                                                </span>
                                                            @endif
                                                            <span class="group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                                                {{ $child->name }}
                                                            </span>
                                                        </span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400" aria-label="{{ __(':count posts', ['count' => $child->posts_count]) }}">
                                                            {{ $child->posts_count }}
                                                        </span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Right Column: Popular Posts --}}
                                @if($category->posts->isNotEmpty())
                                    <div class="border-l border-gray-200 dark:border-gray-700 pl-6">
                                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                                            {{ __('Recent Posts') }}
                                        </h4>
                                        <div class="space-y-3">
                                            @foreach($category->posts as $post)
                                                <a 
                                                    href="{{ route('post.show', $post->slug) }}"
                                                    class="flex gap-3 group focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset rounded-md"
                                                >
                                                    @if($post->featured_image)
                                                        <img 
                                                            src="{{ $post->featured_image }}" 
                                                            alt="{{ $post->title }}"
                                                            class="w-16 h-16 object-cover rounded-md flex-shrink-0"
                                                            loading="lazy"
                                                        />
                                                    @endif
                                                    <div class="flex-1 min-w-0">
                                                        <h5 class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2 mb-1">
                                                            {{ $post->title }}
                                                        </h5>
                                                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                            <time datetime="{{ $post->published_at->toIso8601String() }}">
                                                                {{ $post->published_at->diffForHumans() }}
                                                            </time>
                                                            <span>•</span>
                                                            <span>{{ $post->reading_time }} {{ __('min read') }}</span>
                                                        </div>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- View All Link --}}
                            <a 
                                href="{{ route('category.show', $category->slug) }}"
                                class="block mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors text-center focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset rounded"
                            >
                                {{ __('View all in :category', ['category' => $category->name]) }} →
                            </a>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 px-4">
                    {{ __('No categories available') }}
                </p>
            @endforelse
        </div>

        {{-- Scroll Right Button --}}
        <button 
            x-show="canScrollRight"
            @click="$refs.scrollContainer.scrollBy({ left: 200, behavior: 'smooth' })"
            class="absolute right-0 top-1/2 -translate-y-1/2 z-10 p-2 bg-white dark:bg-gray-800 shadow-lg rounded-full text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            aria-label="{{ __('Scroll categories right') }}"
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
    </nav>
@endif
