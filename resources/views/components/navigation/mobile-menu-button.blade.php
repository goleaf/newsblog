{{--
    Mobile Menu Button Component
    
    Toggles the mobile menu visibility with touch-friendly sizing.
    Requirements: 17.1, 17.2, 17.5
--}}

<button 
    @click="mobileMenuOpen = !mobileMenuOpen"
    type="button"
    class="lg:hidden p-3 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 touch-target"
    aria-label="Toggle mobile menu"
    :aria-expanded="mobileMenuOpen.toString()"
    data-mobile-menu-toggle
>
    {{-- Hamburger Icon with Animation --}}
    <div class="relative w-6 h-6">
        <span 
            class="absolute top-1 left-0 w-6 h-0.5 bg-current transition-all duration-300 ease-in-out"
            :class="mobileMenuOpen ? 'rotate-45 translate-y-2' : ''"
        ></span>
        <span 
            class="absolute top-1/2 left-0 w-6 h-0.5 bg-current -translate-y-1/2 transition-all duration-300 ease-in-out"
            :class="mobileMenuOpen ? 'opacity-0' : 'opacity-100'"
        ></span>
        <span 
            class="absolute bottom-1 left-0 w-6 h-0.5 bg-current transition-all duration-300 ease-in-out"
            :class="mobileMenuOpen ? '-rotate-45 -translate-y-2' : ''"
        ></span>
    </div>
    
    {{-- Screen reader text --}}
    <span class="sr-only" x-text="mobileMenuOpen ? 'Close menu' : 'Open menu'"></span>
</button>
