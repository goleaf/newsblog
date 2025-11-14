@props([
    'variant' => 'default', // default, primary, success, warning, danger, info
    'size' => 'md', // sm, md, lg
    'rounded' => true,
    'removable' => false,
    'removeAction' => null
])

@php
    $variantClasses = [
        'default' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        'primary' => 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    ];
    
    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-0.5 text-sm',
        'lg' => 'px-3 py-1 text-base',
    ];
    
    $roundedClass = $rounded ? 'rounded-full' : 'rounded';
    $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center font-medium {$variantClass} {$sizeClass} {$roundedClass}"]) }}>
    {{ $slot }}
    
    @if($removable)
        <button 
            type="button"
            @if($removeAction)
                @click="{{ $removeAction }}"
            @endif
            class="ml-1 inline-flex items-center justify-center rounded-full hover:bg-black/10 dark:hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-{{ $variant === 'default' ? 'gray' : $variant }}-500"
            aria-label="Remove"
        >
            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    @endif
</span>
