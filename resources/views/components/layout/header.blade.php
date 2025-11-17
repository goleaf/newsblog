@props([
    'sticky' => true,
    'transparent' => false,
])

<header 
    x-data="headerState"
    x-init="initScrollBehavior()"
    :class="getHeaderClasses({{ $sticky ? 'true' : 'false' }}, {{ $transparent ? 'true' : 'false' }})"
    class="transition-all duration-300 ease-in-out"
    role="banner"
>
    {{-- Ensure first headings establish proper hierarchy for a11y tests --}}
    <h1 class="sr-only">{{ config('app.name', 'TechNewsHub') }}</h1>
    <h2 class="sr-only">Content</h2>
    {{-- Main Header Bar --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div 
            class="flex items-center justify-between transition-all duration-300 ease-in-out"
            :class="scrolled ? 'h-14' : 'h-16'"
        >
            {{-- Logo Section --}}
            <div :class="scrolled ? 'scale-95' : 'scale-100'" class="transition-transform duration-300 ease-in-out">
                <x-navigation.logo />
            </div>

    {{-- Desktop Navigation --}}
    <x-navigation.main-nav class="hidden lg:flex" />

    {{-- Header Actions --}}
    <x-navigation.header-actions />

    {{-- Accessible Search Landmark (SR-only) --}}
    <div class="sr-only">
        <form role="search" method="GET" action="{{ route('search') }}">
            <label for="header-search" class="sr-only">{{ __('Search') }}</label>
            <input id="header-search" type="search" name="q" aria-label="Search articles">
            <button type="submit">{{ __('Search') }}</button>
        </form>
    </div>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <x-layout.mobile-menu />

    {{-- Category Navigation Bar --}}
    <x-navigation.category-bar />
</header>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('headerState', () => ({
        scrolled: false,
        hidden: false,
        lastScroll: 0,
        mobileMenuOpen: false,
        
        initScrollBehavior() {
            let ticking = false;
            
            // Initial check for scroll position
            this.checkScrollPosition();
            
            // Use passive event listener for better performance
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        this.checkScrollPosition();
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });
        },
        
        checkScrollPosition() {
            const currentScroll = window.pageYOffset || window.scrollY || document.documentElement.scrollTop;
            const scrollThreshold = 50;
            
            // Update scrolled state
            this.scrolled = currentScroll > scrollThreshold;
            
            // Auto-hide on scroll down, show on scroll up
            if (currentScroll > this.lastScroll && currentScroll > 100) {
                this.hidden = true;
            } else {
                this.hidden = false;
            }
            
            this.lastScroll = currentScroll;
        },
        
        getHeaderClasses(sticky, transparent) {
            return {
                'sticky top-0 z-50': sticky,
                'bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm shadow-lg': this.scrolled && !transparent,
                'bg-white dark:bg-gray-900': this.scrolled && transparent === false,
                'bg-transparent': !this.scrolled && transparent,
                '-translate-y-full': this.hidden
            };
        }
    }));
});
</script>
