@props(['page' => 'default'])

@php
    // Determine which critical CSS to inline based on page type
    $criticalCssMap = [
        'home' => 'critical-home',
        'article' => 'critical-article',
        'category' => 'critical-category',
        'search' => 'critical-search',
        'dashboard' => 'critical-dashboard',
        'default' => 'critical',
    ];
    
    $criticalCssFile = $criticalCssMap[$page] ?? 'critical';
    
    // Get the critical CSS content for inlining
    $criticalCssPath = public_path('build/assets/' . $criticalCssFile . '.css');
    $criticalCss = '';
    
    // In production, inline the critical CSS
    if (app()->environment('production') && file_exists($criticalCssPath)) {
        $criticalCss = file_get_contents($criticalCssPath);
    }
@endphp

@if($criticalCss)
    {{-- Inline Critical CSS for faster initial render --}}
    <style>
        {!! $criticalCss !!}
    </style>
    
    {{-- Preload main CSS --}}
    @vite(['resources/css/app.css'], 'build')
    
    {{-- Defer non-critical CSS using media="print" trick --}}
    <link rel="preload" 
          href="{{ Vite::asset('resources/css/app.css') }}" 
          as="style" 
          onload="this.onload=null;this.rel='stylesheet'">
    
    {{-- Fallback for browsers without JavaScript --}}
    <noscript>
        @vite(['resources/css/app.css'])
    </noscript>
@else
    {{-- Development mode or critical CSS not found - load normally --}}
    @vite(['resources/css/critical.css', 'resources/css/app.css'])
@endif

{{-- Preconnect to external resources if needed --}}
@if(config('services.cdn.enabled', false))
    <link rel="preconnect" href="{{ config('services.cdn.url') }}" crossorigin>
    <link rel="dns-prefetch" href="{{ config('services.cdn.url') }}">
@endif
