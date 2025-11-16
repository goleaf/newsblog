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

    {{-- Optimized CSS Loading --}}
    <x-optimized-css :page="$page ?? 'default'" />
    
    {{-- Additional Styles --}}
    @stack('styles')
    {{-- Print styles (guard when manifest is unavailable in test runs) --}}
    @if(file_exists(public_path('build/manifest.json')))
        <link rel="stylesheet" href="{{ Vite::asset('resources/css/print.css') }}" media="print">
    @endif
    
    {{-- PWA Manifest and theme color --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0ea5e9">
    
    {{-- Preload critical resources --}}
    @stack('preload')
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
        {{ __('a11y.skip_to_main') }}
    </a>

    {{-- Header --}}
    @if(isset($header))
        <header role="banner">
            {{ $header }}
        </header>
    @else
        <x-layout.header />
    @endif

    {{-- Main Content --}}
    <main id="main-content" role="main" tabindex="-1">
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </main>

    {{-- Footer --}}
    @if(isset($footer))
        {{ $footer }}
    @else
        <x-layout.footer />
    @endif

    {{-- Toast Notifications --}}
    <x-ui.toast-notification />

    {{-- Search Modal --}}
    <x-ui.search-modal />
    {{-- Shortcuts Help Modal --}}
    <x-ui.shortcuts-modal />

    {{-- Modal Container --}}
    <div id="modal-container">
        {{-- Modals will be inserted here dynamically --}}
    </div>

    {{-- Core App JS + Page-Specific Scripts (Code Splitting) --}}
    @if(app()->environment('local') || file_exists(public_path('build/manifest.json')))
        @vite(['resources/js/app.js'])
    @endif
    @stack('page-scripts')
    
    {{-- Additional Scripts --}}
    @stack('scripts')
    
    {{-- Cookie Consent Banner (Requirement 16.4) --}}
    <x-gdpr.cookie-consent />
    
    {{-- Scroll to Top Button (Requirement 74) --}}
    <x-ui.scroll-to-top />
</body>
</html>
