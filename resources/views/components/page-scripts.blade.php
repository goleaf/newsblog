@props(['page' => null])

@if($page)
    @vite("resources/js/pages/{$page}.js")
    <script>
        // Initialize page-specific module after Alpine is ready
        document.addEventListener('alpine:init', () => {
            if (window.loadPageModule) {
                window.loadPageModule('{{ $page }}');
            }
        });
    </script>
@endif
