{{--
    Main Navigation Component
    
    Primary navigation links for desktop view.
--}}

@props(['class' => ''])

@php
$navItems = [
    ['route' => 'home', 'label' => 'Home', 'pattern' => 'home'],
    ['route' => 'series.index', 'label' => 'Series', 'pattern' => 'series.*'],
    ['route' => 'search', 'label' => 'Browse', 'pattern' => 'search'],
];
@endphp

<nav {{ $attributes->merge(['class' => 'items-center gap-8']) }} role="navigation" aria-label="Main navigation">
    @foreach($navItems as $item)
        <a 
            href="{{ route($item['route']) }}" 
            class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors font-medium relative group"
            aria-current="{{ request()->routeIs($item['pattern']) ? 'page' : 'false' }}"
        >
            {{ $item['label'] }}
            <span 
                class="absolute -bottom-1 left-0 w-0 h-0.5 bg-blue-600 dark:bg-blue-400 transition-all group-hover:w-full {{ request()->routeIs($item['pattern']) ? '!w-full' : '' }}"
            ></span>
        </a>
    @endforeach
</nav>
