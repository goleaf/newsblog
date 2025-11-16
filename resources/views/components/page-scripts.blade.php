@props(['page' => null])

@if($page)
    @php
        $manifestPath = public_path('build/manifest.json');
        $shouldInclude = app()->environment('local');
        if (! $shouldInclude && file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
            $entryKey = "resources/js/pages/{$page}.js";
            $shouldInclude = array_key_exists($entryKey, $manifest);
        }
    @endphp

    @if($shouldInclude)
        @vite("resources/js/pages/{$page}.js")
    @endif

    <script>
        // Initialize page-specific module after Alpine is ready
        document.addEventListener('alpine:init', () => {
            if (window.loadPageModule) {
                window.loadPageModule('{{ $page }}');
            }
        });
    </script>
@endif
