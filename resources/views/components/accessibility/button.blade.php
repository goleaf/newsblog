@props([
    'label',
    'icon' => null,
    'iconOnly' => false,
    'loading' => false,
    'disabled' => false,
    'ariaLabel' => null,
    'ariaDescribedby' => null,
    'ariaExpanded' => null,
    'ariaControls' => null,
    'ariaPressed' => null,
])

@php
    // Use ariaLabel if provided, otherwise use label
    $accessibleLabel = $ariaLabel ?? $label;
@endphp

<button
    type="{{ $attributes->get('type', 'button') }}"
    {{ $disabled || $loading ? 'disabled' : '' }}
    aria-label="{{ $accessibleLabel }}"
    @if($ariaDescribedby) aria-describedby="{{ $ariaDescribedby }}" @endif
    @if($ariaExpanded !== null) aria-expanded="{{ $ariaExpanded ? 'true' : 'false' }}" @endif
    @if($ariaControls) aria-controls="{{ $ariaControls }}" @endif
    @if($ariaPressed !== null) aria-pressed="{{ $ariaPressed ? 'true' : 'false' }}" @endif
    @if($loading) aria-busy="true" @endif
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed']) }}
>
    @if($loading)
        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="sr-only">{{ __('a11y.labels.loading') }}</span>
    @endif

    @if($icon && !$loading)
        <span aria-hidden="true">
            {!! $icon !!}
        </span>
    @endif

    @if(!$iconOnly || $loading)
        <span>{{ $label }}</span>
    @else
        <span class="sr-only">{{ $label }}</span>
    @endif

    {{ $slot }}
</button>
