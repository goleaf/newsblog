@props([
    'src',
    'alt' => '',
    'width' => null,
    'height' => null,
    'class' => '',
    'sizes' => null,
    'srcset' => null,
    'webp' => null,
    'loading' => 'lazy',
    'decoding' => 'async',
    'fetchpriority' => null,
])

@php
    $attributes = $attributes->merge([
        'class' => $class,
        'loading' => $loading,
        'decoding' => $decoding,
    ]);
    
    if ($width) {
        $attributes = $attributes->merge(['width' => $width]);
    }
    
    if ($height) {
        $attributes = $attributes->merge(['height' => $height]);
    }
    
    if ($fetchpriority) {
        $attributes = $attributes->merge(['fetchpriority' => $fetchpriority]);
    }
@endphp

@if($webp)
    <picture>
        <source type="image/webp" srcset="{{ $webp }}" @if($sizes) sizes="{{ $sizes }}" @endif>
        <img src="{{ $src }}" alt="{{ $alt }}" @if($srcset) srcset="{{ $srcset }}" @endif @if($sizes) sizes="{{ $sizes }}" @endif {{ $attributes }}>
    </picture>
@else
    <img src="{{ $src }}" alt="{{ $alt }}" @if($srcset) srcset="{{ $srcset }}" @endif @if($sizes) sizes="{{ $sizes }}" @endif {{ $attributes }}>
@endif
