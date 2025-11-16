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
    {{-- Main Header Bar --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            {{-- Logo Section --}}
            <x-navigation.logo />

            {{-- Desktop Navigation --}}
            <x-navigation.main-nav class="hidden lg:flex" />

            {{-- Header Actions --}}
            <x-navigation.header-actions />
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
            
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        const currentScroll = window.pageYOffset;
                        this.scrolled = currentScroll > 50;
                        
                        if (currentScroll > this.lastScroll && currentScroll > 100) {
                            this.hidden = true;
                        } else {
                            this.hidden = false;
                        }
                        
                        this.lastScroll = currentScroll;
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        },
        
        getHeaderClasses(sticky, transparent) {
            return {
                'sticky top-0 z-50': sticky,
                'bg-white dark:bg-gray-900 shadow-md': this.scrolled && !transparent,
                'bg-transparent': !this.scrolled && transparent,
                '-translate-y-full': this.hidden
            };
        }
    }));
});
</script>
