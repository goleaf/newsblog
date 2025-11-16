{{--
    Mobile Menu Navigation Component
    
    Main navigation links for mobile menu.
--}}

@php
$mobileNavItems = [
    [
        'route' => 'home',
        'label' => 'Home',
        'pattern' => 'home',
        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
    ],
    [
        'route' => 'series.index',
        'label' => 'Series',
        'pattern' => 'series.*',
        'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'
    ],
    [
        'route' => 'search',
        'label' => 'Browse Articles',
        'pattern' => 'search',
        'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'
    ],
    [
        'route' => 'bookmarks.index',
        'label' => 'My Bookmarks',
        'pattern' => 'bookmarks.*',
        'icon' => 'M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z'
    ],
];
@endphp

<nav class="flex-1 px-4 py-6 space-y-1" aria-label="Mobile navigation">
    @foreach($mobileNavItems as $item)
        @php
            $isActive = request()->routeIs($item['pattern']);
            $activeClasses = 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400';
            $inactiveClasses = 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800';
        @endphp
        
        <a 
            href="{{ route($item['route']) }}" 
            class="flex items-center px-4 py-3 text-base font-medium rounded-lg transition-colors {{ $isActive ? $activeClasses : $inactiveClasses }}"
            @click="mobileMenuOpen = false"
            aria-current="{{ $isActive ? 'page' : 'false' }}"
        >
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
            </svg>
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
