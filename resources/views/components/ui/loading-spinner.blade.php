@props([
    'size' => 'md', // sm, md, lg, xl
    'color' => 'primary',
    'text' => null
])

@php
    $sizeClasses = [
        'sm' => 'w-4 h-4',
        'md' => 'w-8 h-8',
        'lg' => 'w-12 h-12',
        'xl' => 'w-16 h-16'
    ];
    
    $colorClasses = [
        'primary' => 'border-primary-600',
        'white' => 'border-white',
        'gray' => 'border-gray-600',
        'accent' => 'border-accent-600'
    ];
    
    $spinnerSize = $sizeClasses[$size] ?? $sizeClasses['md'];
    $spinnerColor = $colorClasses[$color] ?? $colorClasses['primary'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center']) }} role="status" aria-live="polite">
    <div class="animate-spin rounded-full border-b-2 {{ $spinnerSize }} {{ $spinnerColor }}"></div>
    
    @if($text)
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $text }}</p>
    @endif
    
    <span class="sr-only">Loading...</span>
</div>
