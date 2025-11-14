<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Title and Meta Tags --}}
    <title>{{ $title ?? config('app.name', 'TechNewsHub') }}</title>
    <meta name="description" content="{{ $description ?? config('app.description', 'Your source for technology news and insights') }}">
    
    {{-- Additional Meta Tags --}}
    @stack('meta-tags')
    
    {{-- Structured Data --}}
    @stack('structured-data')

    {{-- Theme Script (Prevent Flash of Unstyled Content) --}}
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'system';
            const isDark = theme === 'dark' || 
                          (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    {{-- Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    {{-- Additional Styles --}}
    @stack('styles')
</head>
<body class="preload bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased">
    {{-- Remove preload class after page load to enable transitions --}}
    <script>
        window.addEventListener('load', () => {
            document.body.classList.remove('preload');
        });
    </script>
    {{-- Skip to Main Content Link (Accessibility) --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-blue-600 focus:text-white focus:rounded-md">
        Skip to main content
    </a>

    {{-- Header --}}
    @if(isset($header))
        <header>
            {{ $header }}
        </header>
    @else
        <x-layout.header />
    @endif

    {{-- Main Content --}}
    <main id="main-content" tabindex="-1">
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </main>

    {{-- Footer Slot --}}
    <footer>
        {{ $footer ?? '' }}
    </footer>

    {{-- Toast Notifications --}}
    <x-ui.toast-notification />

    {{-- Search Modal --}}
    <x-ui.search-modal />

    {{-- Modal Container --}}
    <div id="modal-container">
        {{-- Modals will be inserted here dynamically --}}
    </div>

    {{-- Additional Scripts --}}
    @stack('scripts')
    
    {{-- Cookie Consent Banner (Requirement 16.4) --}}
    <x-gdpr.cookie-consent />
</body>
</html>
