{{--
    Category Bar Component
    
    Displays category navigation with responsive behavior.
--}}

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
