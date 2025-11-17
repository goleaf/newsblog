@props(['page' => null])

@if ($page)
    @php
        // Only include the page bundle if the Vite dev server is running (hot file),
        // or if the built manifest actually contains the entry. This avoids
        // throwing an exception in local when dev server isn't running and the
        // manifest is stale or missing the page key.
        $hotPath = public_path('hot');
        $manifestPath = public_path('build/manifest.json');
        $entryKey = "resources/js/pages/{$page}.js";

        $shouldInclude = file_exists($hotPath);

        if (! $shouldInclude && file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
            $shouldInclude = array_key_exists($entryKey, $manifest);
        }
    @endphp

    @if ($shouldInclude)
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
