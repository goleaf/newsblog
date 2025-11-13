@props([
    'src',
    'alt' => '',
    'class' => '',
    'eager' => false,
    'width' => null,
    'height' => null,
])

<img 
    src="{{ $src }}" 
    alt="{{ $alt }}"
    @if($width) width="{{ $width }}" @endif
    @if($height) height="{{ $height }}" @endif
    loading="{{ $eager ? 'eager' : 'lazy' }}"
    decoding="async"
    class="{{ $class }}"
    {{ $attributes }}
>
