{{-- WebSite Schema.org Structured Data (JSON-LD) --}}
<script type="application/ld+json">
@php
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => config('app.name', 'TechNewsHub'),
    'url' => url('/'),
    'description' => config('app.description', 'Your source for technology news and insights'),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => [
            '@type' => 'EntryPoint',
            'urlTemplate' => route('search') . '?q={search_term_string}',
        ],
        'query-input' => 'required name=search_term_string',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => config('app.name', 'TechNewsHub'),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => asset('images/logo.png'),
        ],
    ],
];
@endphp
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
