@php
    $shortcuts = app(\App\Services\AccessibilityService::class)->getKeyboardShortcuts();
@endphp

<div 
    x-data="{ open: false }"
    @keydown.window.shift.slash="open = true"
    @keydown.window.escape="open = false"
>
    <!-- Trigger Button (Hidden, activated by keyboard shortcut) -->
    <button 
        @click="open = true" 
        data-shortcuts-modal
        class="sr-only"
        aria-label="{{ __('a11y.shortcuts.help') }}"
    >
        {{ __('a11y.shortcuts.help') }}
    </button>

    <!-- Modal -->
    <div 
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="shortcuts-modal-title"
        role="dialog"
        aria-modal="true"
        :aria-hidden="!open"
    >
        <!-- Backdrop -->
        <div 
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
            @click="open = false"
        ></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div 
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6"
            >
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <h2 id="shortcuts-modal-title" class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ __('Keyboard Shortcuts') }}
                    </h2>
                    <button 
                        @click="open = false"
                        data-close-modal
                        class="rounded-md text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        aria-label="{{ __('a11y.labels.close') }}"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Shortcuts List -->
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ __('Use these keyboard shortcuts to navigate the site more efficiently.') }}
                    </p>

                    <div class="grid grid-cols-1 gap-3">
                        @foreach($shortcuts as $shortcut)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $shortcut['description'] }}
                                </span>
                                <kbd class="px-3 py-1.5 text-sm font-semibold text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm">
                                    {{ $shortcut['key'] }}
                                </kbd>
                            </div>
                        @endforeach
                    </div>

                    <!-- Additional Tips -->
                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                            {{ __('Accessibility Tips') }}
                        </h3>
                        <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                            <li>{{ __('Use Tab to navigate between interactive elements') }}</li>
                            <li>{{ __('Use Shift+Tab to navigate backwards') }}</li>
                            <li>{{ __('Use Enter or Space to activate buttons and links') }}</li>
                            <li>{{ __('Use Arrow keys to navigate within menus and lists') }}</li>
                            <li>{{ __('Press Escape to close modals and dropdowns') }}</li>
                        </ul>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-6 flex justify-end">
                    <button 
                        @click="open = false"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    >
                        {{ __('Got it') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
