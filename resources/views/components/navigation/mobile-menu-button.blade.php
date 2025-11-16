{{--
    Mobile Menu Button Component
    
    Toggles the mobile menu visibility.
--}}

<button 
    @click="mobileMenuOpen = !mobileMenuOpen"
    type="button"
    class="lg:hidden p-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
    aria-label="Toggle mobile menu"
    :aria-expanded="mobileMenuOpen.toString()"
>
    {{-- Hamburger Icon --}}
    <svg 
        x-show="!mobileMenuOpen" 
        class="w-6 h-6" 
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
        aria-hidden="true"
    >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
    
    {{-- Close Icon --}}
    <svg 
        x-show="mobileMenuOpen" 
        x-cloak
        class="w-6 h-6" 
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
        aria-hidden="true"
    >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>
</button>
