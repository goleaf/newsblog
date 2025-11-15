@props(['post'])

@php
    $metaTags = $post->getMetaTags();
    $structuredData = $post->getStructuredData();
@endphp

{{-- Basic Meta Tags --}}
<title>{{ $metaTags['title'] }} | {{ config('app.name') }}</title>
<meta name="description" content="{{ $metaTags['description'] }}">
@if(!empty($metaTags['keywords']))
    <meta name="keywords" content="{{ $metaTags['keywords'] }}">
@endif

{{-- Author --}}
<meta name="author" content="{{ $post->user->name }}">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">

{{-- Canonical URL --}}
<link rel="canonical" href="{{ $metaTags['og:url'] }}">

{{-- Open Graph Meta Tags --}}
<meta property="og:title" content="{{ $metaTags['og:title'] }}">
<meta property="og:description" content="{{ $metaTags['og:description'] }}">
<meta property="og:image" content="{{ $metaTags['og:image'] }}">
<meta property="og:url" content="{{ $metaTags['og:url'] }}">
<meta property="og:type" content="{{ $metaTags['og:type'] }}">
<meta property="og:site_name" content="{{ $metaTags['og:site_name'] }}">

{{-- Open Graph Article Tags --}}
@if(!empty($metaTags['article:published_time']))
    <meta property="article:published_time" content="{{ $metaTags['article:published_time'] }}">
@endif
<meta property="article:modified_time" content="{{ $metaTags['article:modified_time'] }}">
<meta property="article:author" content="{{ $metaTags['article:author'] }}">
<meta property="article:section" content="{{ $metaTags['article:section'] }}">
@if(is_array($metaTags['article:tag']))
    @foreach($metaTags['article:tag'] as $tag)
        <meta property="article:tag" content="{{ $tag }}">
    @endforeach
@endif

{{-- Twitter Card Meta Tags --}}
<meta name="twitter:card" content="{{ $metaTags['twitter:card'] }}">
<meta name="twitter:title" content="{{ $metaTags['twitter:title'] }}">
<meta name="twitter:description" content="{{ $metaTags['twitter:description'] }}">
<meta name="twitter:image" content="{{ $metaTags['twitter:image'] }}">
<meta name="twitter:url" content="{{ $metaTags['twitter:url'] }}">

{{-- Schema.org Article Structured Data (JSON-LD) --}}
<script type="application/ld+json">
{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

{{-- BreadcrumbList Structured Data --}}
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => route('home')
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $post->category->name,
            'item' => route('category.show', $post->category->slug)
        ],
        [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $post->title,
            'item' => route('post.show', $post->slug)
        ]
    ]
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
