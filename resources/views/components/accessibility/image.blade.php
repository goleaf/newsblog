@props([
    'src',
    'alt',
    'decorative' => false,
    'longdesc' => null,
    'caption' => null,
])

@php
    // If image is decorative, use empty alt text
    $altText = $decorative ? '' : $alt;
    
    // Validate alt text if not decorative
    if (!$decorative && $alt) {
        $validation = app(\App\Services\AccessibilityService::class)->validateAltText($alt);
        if (!$validation['valid'] && app()->environment('local')) {
            // Log warning in development
            \Illuminate\Support\Facades\Log::warning('Alt text validation failed', [
                'src' => $src,
                'alt' => $alt,
                'issues' => $validation['issues'],
            ]);
        }
    }
@endphp

@if($caption)
    <figure {{ $attributes->only('class') }}>
        <img 
            src="{{ $src }}" 
            alt="{{ $altText }}"
            @if($longdesc) aria-describedby="{{ $longdesc }}" @endif
            @if($decorative) role="presentation" @endif
            {{ $attributes->except('class') }}
        >
        <figcaption class="text-sm text-gray-600 dark:text-gray-400 mt-2">
            {{ $caption }}
        </figcaption>
    </figure>
@else
    <img 
        src="{{ $src }}" 
        alt="{{ $altText }}"
        @if($longdesc) aria-describedby="{{ $longdesc }}" @endif
        @if($decorative) role="presentation" @endif
        {{ $attributes }}
    >
@endif
