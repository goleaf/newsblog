{{-- Mobile Menu Overlay and Slide-in Panel --}}
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
        class="fixed inset-0 bg-black bg-opacity-50"
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
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <a href="{{ route('home') }}" class="flex items-center space-x-2">
                    <x-application-logo class="h-8 w-auto" />
                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ config('app.name', 'TechNewsHub') }}
                    </span>
                </a>
                <button 
                    @click="mobileMenuOpen = false"
                    class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    aria-label="Close menu"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Navigation Links --}}
            <nav class="flex-1 px-4 py-6 space-y-1" aria-label="Mobile navigation">
                <a 
                    href="{{ route('home') }}" 
                    class="flex items-center px-4 py-3 text-base font-medium rounded-lg transition-colors
                           {{ request()->routeIs('home') 
                              ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' 
                              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                    @click="mobileMenuOpen = false"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Home
                </a>

                <a 
                    href="{{ route('series.index') }}" 
                    class="flex items-center px-4 py-3 text-base font-medium rounded-lg transition-colors
                           {{ request()->routeIs('series.*') 
                              ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' 
                              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                    @click="mobileMenuOpen = false"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Series
                </a>

                <a 
                    href="{{ route('search') }}" 
                    class="flex items-center px-4 py-3 text-base font-medium rounded-lg transition-colors
                           {{ request()->routeIs('search') 
                              ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' 
                              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                    @click="mobileMenuOpen = false"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Browse Articles
                </a>

                <a 
                    href="{{ route('bookmarks.index') }}" 
                    class="flex items-center px-4 py-3 text-base font-medium rounded-lg transition-colors
                           {{ request()->routeIs('bookmarks.*') 
                              ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' 
                              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                    @click="mobileMenuOpen = false"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                    My Bookmarks
                </a>
            </nav>

            {{-- User Menu (Mobile) --}}
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                @auth
                    <div class="space-y-1">
                        <a 
                            href="{{ route('dashboard') }}" 
                            class="flex items-center px-4 py-3 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                            @click="mobileMenuOpen = false"
                        >
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Dashboard
                        </a>
                        <a 
                            href="{{ route('bookmarks.index') }}" 
                            class="flex items-center px-4 py-3 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                            @click="mobileMenuOpen = false"
                        >
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                            </svg>
                            Bookmarks
                        </a>
                        <a 
                            href="{{ route('profile.edit') }}" 
                            class="flex items-center px-4 py-3 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                            @click="mobileMenuOpen = false"
                        >
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Settings
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button 
                                type="submit"
                                class="w-full flex items-center px-4 py-3 text-base font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                @else
                    <div class="space-y-2">
                        <a 
                            href="{{ route('login') }}" 
                            class="block w-full px-4 py-3 text-center text-base font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                            @click="mobileMenuOpen = false"
                        >
                            Login
                        </a>
                        <a 
                            href="{{ route('register') }}" 
                            class="block w-full px-4 py-3 text-center text-base font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                            @click="mobileMenuOpen = false"
                        >
                            Register
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</div>
