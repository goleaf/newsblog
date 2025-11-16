@props([
    'currentSort' => 'latest',
    'categorySlug' => '',
])

@php
$sortOptions = [
    'latest' => ['label' => 'Latest First', 'icon' => 'M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12'],
    'oldest' => ['label' => 'Oldest First', 'icon' => 'M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4'],
    'popular' => ['label' => 'Most Popular', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
];

$currentOption = $sortOptions[$currentSort] ?? $sortOptions['latest'];
@endphp

<div 
    x-data="{ open: false }"
    @click.away="open = false"
    class="relative inline-block text-left"
>
    <!-- Sort Button -->
    <button 
        @click="open = !open"
        type="button"
        class="inline-flex items-center justify-between w-full sm:w-auto px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm"
        aria-haspopup="true"
        :aria-expanded="open.toString()"
    >
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $currentOption['icon'] }}"></path>
            </svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Sort: <span class="text-gray-900 dark:text-white">{{ $currentOption['label'] }}</span>
            </span>
        </div>
        <svg 
            class="ml-2 w-5 h-5 text-gray-400 transition-transform"
            :class="{ 'rotate-180': open }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute right-0 z-50 mt-2 w-56 origin-top-right bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 ring-1 ring-black ring-opacity-5 focus:outline-none"
        role="menu"
        aria-orientation="vertical"
    >
        <div class="py-1" role="none">
            @foreach($sortOptions as $value => $option)
                @php
                    $url = route('category.show', $categorySlug);
                    $params = request()->except(['sort', 'page']);
                    $params['sort'] = $value;
                    $url = $url . '?' . http_build_query($params);
                @endphp
                <a 
                    href="{{ $url }}"
                    class="group flex items-center gap-3 px-4 py-3 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $currentSort === $value ? 'bg-gray-50 dark:bg-gray-900' : '' }}"
                    role="menuitem"
                >
                    <svg class="w-5 h-5 {{ $currentSort === $value ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $option['icon'] }}"></path>
                    </svg>
                    <span class="{{ $currentSort === $value ? 'text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ $option['label'] }}
                    </span>
                    @if($currentSort === $value)
                        <svg class="ml-auto w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </a>
            @endforeach
        </div>
        
        <!-- Sort Description -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                @switch($currentSort)
                    @case('latest')
                        Showing most recently published articles first
                        @break
                    @case('oldest')
                        Showing oldest published articles first
                        @break
                    @case('popular')
                        Showing articles with most views
                        @break
                    @default
                        Sort articles by different criteria
                @endswitch
            </p>
        </div>
    </div>
</div>


