@props([
    'level' => 2,
    'visualLevel' => null,
    'id' => null,
])

@php
    // Ensure level is between 1 and 6
    $level = max(1, min(6, $level));
    $visualLevel = $visualLevel ?? $level;
    
    // Map visual levels to Tailwind classes
    $sizeClasses = [
        1 => 'text-4xl font-bold',
        2 => 'text-3xl font-bold',
        3 => 'text-2xl font-semibold',
        4 => 'text-xl font-semibold',
        5 => 'text-lg font-medium',
        6 => 'text-base font-medium',
    ];
    
    $sizeClass = $sizeClasses[$visualLevel] ?? $sizeClasses[2];
@endphp

<h{{ $level }} 
    {{ $attributes->merge(['class' => $sizeClass . ' text-gray-900 dark:text-white']) }}
    @if($id) id="{{ $id }}" @endif
>
    {{ $slot }}
</h{{ $level }}>
