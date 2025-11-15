@props(['page' => 'default'])

@php
    // Determine if we're in production
    $isProduction = app()->environment('production');
    
    // Get critical CSS path
    $criticalCssPath = public_path('build/assets/critical.css');
    $criticalCss = '';
    
    // In production, inline critical CSS
    if ($isProduction && file_exists($criticalCssPath)) {
        $criticalCss = file_get_contents($criticalCssPath);
    }
@endphp

@if($isProduction && !empty($criticalCss))
    {{-- Inline critical CSS for faster initial render --}}
    <style id="critical-css">
        {!! $criticalCss !!}
    </style>
    
    {{-- Preload main CSS --}}
    <link rel="preload" href="{{ Vite::asset('resources/css/app.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    {{-- Fallback for browsers without JavaScript --}}
    <noscript>
        @vite(['resources/css/app.css'])
    </noscript>
    
    {{-- Load main CSS asynchronously --}}
    <script>
        // Load CSS asynchronously
        (function() {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '{{ Vite::asset('resources/css/app.css') }}';
            document.head.appendChild(link);
            
            // Remove critical CSS once main CSS is loaded to avoid duplication
            link.onload = function() {
                var criticalCss = document.getElementById('critical-css');
                if (criticalCss) {
                    setTimeout(function() {
                        criticalCss.remove();
                    }, 100);
                }
            };
        })();
    </script>
@else
    {{-- Development mode: load CSS normally --}}
    @vite(['resources/css/app.css'])
@endif

{{-- Preconnect to external resources --}}
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="dns-prefetch" href="https://fonts.googleapis.com">
