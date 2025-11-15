@props([
    'sticky' => true,
    'transparent' => false,
])

<header 
    x-data="{ 
        scrolled: false,
        hidden: false,
        lastScroll: 0,
        mobileMenuOpen: false
    }"
    x-init="
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const currentScroll = window.pageYOffset;
                    scrolled = currentScroll > 50;
                    
                    // Hide on scroll down, show on scroll up
                    if (currentScroll > lastScroll && currentScroll > 100) {
                        hidden = true;
                    } else {
                        hidden = false;
                    }
                    lastScroll = currentScroll;
                    ticking = false;
                });
                ticking = true;
            }
        });
    "
    :class="{
        'sticky top-0 z-50': {{ $sticky ? 'true' : 'false' }},
        'bg-white dark:bg-gray-900 shadow-md': scrolled && !{{ $transparent ? 'true' : 'false' }},
        'bg-transparent': !scrolled && {{ $transparent ? 'true' : 'false' }},
        '-translate-y-full': hidden
    }"
    class="transition-all duration-300 ease-in-out"
    role="banner"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            {{-- Logo --}}
            <div class="flex-shrink-0">
                <a href="{{ route('home') }}" class="flex items-center space-x-2" aria-label="TechNewsHub Home">
                    <x-application-logo class="h-8 w-auto" />
                    <span class="text-xl font-bold text-gray-900 dark:text-white hidden sm:inline">
                        {{ config('app.name', 'TechNewsHub') }}
                    </span>
                </a>
            </div>

            {{-- Desktop Navigation --}}
            <nav class="hidden lg:flex items-center gap-8" aria-label="Main navigation">
                <a 
                    href="{{ route('home') }}" 
                    class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors font-medium"
                    aria-current="{{ request()->routeIs('home') ? 'page' : 'false' }}"
                >
                    Home
                </a>
                <a 
                    href="{{ route('series.index') }}" 
                    class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors font-medium"
                    aria-current="{{ request()->routeIs('series.*') ? 'page' : 'false' }}"
                >
                    Series
                </a>
                <a 
                    href="{{ route('search') }}" 
                    class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors font-medium"
                    aria-current="{{ request()->routeIs('search') ? 'page' : 'false' }}"
                >
                    Browse
                </a>
            </nav>

            {{-- Right Side Actions --}}
            <div class="flex items-center gap-4">
                {{-- Search Icon --}}
                <button 
                    @click="$dispatch('open-search')"
                    class="p-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                    aria-label="Open search"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>

                {{-- Dark Mode Toggle --}}
                <x-ui.dark-mode-toggle />

                {{-- Notifications (Authenticated Users Only) --}}
                @auth
                <div class="hidden lg:block">
                    <x-notifications.dropdown :unreadCount="auth()->user()->notifications()->unread()->count()" />
                </div>
                @endauth

                {{-- User Menu (Desktop) --}}
                <div class="hidden lg:block">
                    <x-navigation.user-menu />
                </div>

                {{-- Mobile Menu Button --}}
                <button 
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="lg:hidden p-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                    aria-label="Toggle mobile menu"
                    aria-expanded="false"
                    :aria-expanded="mobileMenuOpen.toString()"
                >
                    <svg 
                        x-show="!mobileMenuOpen" 
                        class="w-6 h-6" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg 
                        x-show="mobileMenuOpen" 
                        x-cloak
                        class="w-6 h-6" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <x-layout.mobile-menu />

    {{-- Category Navigation Bar (Desktop & Mobile) --}}
    <div class="border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Desktop: Horizontal scroll with mega menu --}}
            <div class="hidden md:block">
                <x-navigation.category-menu />
            </div>
            
            {{-- Mobile: Horizontal scroll --}}
            <div class="md:hidden py-3">
                <x-navigation.category-menu :mobile="false" />
            </div>
        </div>
    </div>
</header>
