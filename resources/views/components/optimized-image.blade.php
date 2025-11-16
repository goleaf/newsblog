@props([
    'src',
    'alt' => '',
    'class' => '',
    'eager' => false,
    'width' => null,
    'height' => null,
    'sizes' => null,
    'blurUp' => true,
])

@php
    // Generate responsive image URLs if the src is a storage path
    $isStoragePath = str_contains($src, '/storage/');
    $srcset = null;
    
    if ($isStoragePath && !$eager) {
        // Generate srcset for different sizes
        $srcset = implode(', ', [
            $src . '?w=400 400w',
            $src . '?w=800 800w',
            $src . '?w=1200 1200w',
            $src . '?w=1600 1600w',
        ]);
        
        // Default sizes if not provided
        if (!$sizes) {
            $sizes = '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw';
        }
    }
    
    // Blur-up placeholder: tiny base64 encoded SVG
    $placeholder = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 ' . ($width ?: '800') . ' ' . ($height ?: '600') . '\'%3E%3Cfilter id=\'b\' color-interpolation-filters=\'sRGB\'%3E%3CfeGaussianBlur stdDeviation=\'.5\'%3E%3C/feGaussianBlur%3E%3CfeComponentTransfer%3E%3CfeFuncA type=\'discrete\' tableValues=\'1 1\'%3E%3C/feFuncA%3E%3C/feComponentTransfer%3E%3C/filter%3E%3Cg filter=\'url(%23b)\'%3E%3Crect fill=\'%23e5e7eb\' width=\'100%25\' height=\'100%25\'/%3E%3C/g%3E%3C/svg%3E';
@endphp

@php
    $classes = trim($class . ($blurUp && !$eager ? ' lazy-image' : ''));
@endphp

@php
    $supportsWebp = preg_match('/\.(jpg|jpeg|png)$/i', $src);
    $webpSrc = $supportsWebp ? preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $src) : null;
    $webpSrcset = null;
    if ($supportsWebp && $isStoragePath && !$eager) {
        $webpSrcset = implode(', ', [
            ($webpSrc ?? $src) . '?w=400&format=webp 400w',
            ($webpSrc ?? $src) . '?w=800&format=webp 800w',
            ($webpSrc ?? $src) . '?w=1200&format=webp 1200w',
            ($webpSrc ?? $src) . '?w=1600&format=webp 1600w',
        ]);
    }
@endphp

<picture>
    @if($supportsWebp)
        @if($blurUp && !$eager)
            <source type="image/webp"
                    @if($webpSrcset) data-srcset="{{ $webpSrcset }}" @endif
                    @if($sizes) data-sizes="{{ $sizes }}" @endif>
        @else
            <source type="image/webp"
                    srcset="{{ $webpSrcset ?: (($webpSrc ?? $src) . ' 1x') }}"
                    @if($sizes) sizes="{{ $sizes }}" @endif>
        @endif
    @endif
    <img 
        @if($blurUp && !$eager)
            src="{{ $placeholder }}"
            data-src="{{ $src }}"
            @if($srcset) data-srcset="{{ $srcset }}" @endif
            @if($sizes) data-sizes="{{ $sizes }}" @endif
        @else
            src="{{ $src }}"
            @if($srcset) srcset="{{ $srcset }}" @endif
            @if($sizes) sizes="{{ $sizes }}" @endif
        @endif
        alt="{{ $alt }}"
        @if($width) width="{{ $width }}" @endif
        @if($height) height="{{ $height }}" @endif
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        decoding="async"
        @if($classes) class="{{ $classes }}" @endif
        {{ $attributes }}
    >
</picture>

@if($blurUp && !$eager)
    @once
        @push('scripts')
        <script>
            // Lazy loading with blur-up effect
            document.addEventListener('DOMContentLoaded', function() {
                const lazyImages = document.querySelectorAll('img.lazy-image');
                
                if ('IntersectionObserver' in window) {
                    const imageObserver = new IntersectionObserver((entries, observer) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const img = entry.target;
                                
                                // Load the actual image
                                if (img.dataset.src) {
                                    img.src = img.dataset.src;
                                }
                                if (img.dataset.srcset) {
                                    img.srcset = img.dataset.srcset;
                                }
                                if (img.dataset.sizes) {
                                    img.sizes = img.dataset.sizes;
                                }
                                
                                // Add loaded class for fade-in effect
                                img.addEventListener('load', () => {
                                    img.classList.add('lazy-loaded');
                                });
                                
                                // Remove lazy-image class and stop observing
                                img.classList.remove('lazy-image');
                                observer.unobserve(img);
                            }
                        });
                    }, {
                        rootMargin: '50px 0px', // Start loading 50px before entering viewport
                        threshold: 0.01
                    });
                    
                    lazyImages.forEach(img => imageObserver.observe(img));
                } else {
                    // Fallback for browsers without IntersectionObserver
                    lazyImages.forEach(img => {
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                        }
                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                        }
                        if (img.dataset.sizes) {
                            img.sizes = img.dataset.sizes;
                        }
                        img.classList.remove('lazy-image');
                        img.classList.add('lazy-loaded');
                    });
                }
            });
        </script>
        @endpush
        
        @push('styles')
        <style>
            /* Blur-up effect styles */
            img.lazy-image {
                filter: blur(10px);
                transition: filter 0.3s ease-out;
            }
            
            img.lazy-loaded {
                filter: blur(0);
            }
            
            /* Prevent layout shift */
            img[width][height] {
                height: auto;
            }
        </style>
        @endpush
    @endonce
@endif
