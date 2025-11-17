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
    
    $criticalCssEntry = $criticalCssMap[$page] ?? 'critical';
    
    // Resolve critical CSS file via Vite manifest to support hashed filenames and proper dirs
    $criticalCss = '';
    if (app()->environment('production')) {
        $manifestPath = public_path('build/manifest.json');
        $resourceKey = 'resources/css/' . $criticalCssEntry . '.css';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
            if (isset($manifest[$resourceKey]['file'])) {
                $builtFile = public_path('build/' . $manifest[$resourceKey]['file']);
                if (file_exists($builtFile)) {
                    $criticalCss = file_get_contents($builtFile);
                }
            }
        }
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
          href="{{ \Illuminate\Support\Facades\Vite::asset('resources/css/app.css') }}" 
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
