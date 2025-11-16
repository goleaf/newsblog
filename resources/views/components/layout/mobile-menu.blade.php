{{--
    Mobile Menu Component
    
    Slide-in navigation panel for mobile devices.
--}}

<div 
    x-show="mobileMenuOpen"
    x-cloak
    class="lg:hidden fixed inset-0 z-50"
    role="dialog"
    aria-modal="true"
    aria-label="Mobile navigation menu"
>
    {{-- Overlay --}}
    <div 
        x-show="mobileMenuOpen"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileMenuOpen = false"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
        aria-hidden="true"
    ></div>

    {{-- Slide-in Menu Panel --}}
    <div 
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 w-full max-w-sm bg-white dark:bg-gray-900 shadow-xl overflow-y-auto"
        @click.away="mobileMenuOpen = false"
        x-trap="mobileMenuOpen"
    >
        <div class="flex flex-col h-full">
            {{-- Header --}}
            <x-navigation.mobile-menu-header />

            {{-- Navigation Links --}}
            <x-navigation.mobile-menu-nav />

            {{-- User Section --}}
            <x-navigation.mobile-menu-user />
        </div>
    </div>
</div>
