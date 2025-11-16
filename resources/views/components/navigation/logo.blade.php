{{--
    Logo Component
    
    Displays the application logo and name with proper accessibility attributes.
--}}

<div class="flex-shrink-0">
    <a 
        href="{{ route('home') }}" 
        class="flex items-center gap-2 group" 
        aria-label="{{ config('app.name', 'TechNewsHub') }} Home"
    >
        <x-application-logo class="h-8 w-auto transition-transform group-hover:scale-105" />
        <span class="text-xl font-bold text-gray-900 dark:text-white hidden sm:inline transition-colors">
            {{ config('app.name', 'TechNewsHub') }}
        </span>
    </a>
</div>
