{{--
    Mobile Menu Header Component
    
    Header section of the mobile menu with logo and close button.
--}}

<div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
    <a href="{{ route('home') }}" class="flex items-center gap-2 group">
        <x-application-logo class="h-8 w-auto transition-transform group-hover:scale-105" />
        <span class="text-xl font-bold text-gray-900 dark:text-white">
            {{ config('app.name', 'TechNewsHub') }}
        </span>
    </a>
    
    <button 
        @click="mobileMenuOpen = false"
        type="button"
        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
        aria-label="Close menu"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>
