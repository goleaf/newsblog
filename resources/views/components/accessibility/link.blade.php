@props([
    'href',
    'external' => false,
    'ariaLabel' => null,
    'ariaDescribedby' => null,
    'ariaCurrent' => null,
])

@php
    // Determine if link is external
    $isExternal = $external || (filter_var($href, FILTER_VALIDATE_URL) && !str_starts_with($href, config('app.url')));
    
    // Build rel attribute for external links
    $rel = $isExternal ? 'noopener noreferrer' : null;
    
    // Build target attribute
    $target = $isExternal ? '_blank' : null;
@endphp

<a 
    href="{{ $href }}"
    @if($target) target="{{ $target }}" @endif
    @if($rel) rel="{{ $rel }}" @endif
    @if($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
    @if($ariaDescribedby) aria-describedby="{{ $ariaDescribedby }}" @endif
    @if($ariaCurrent) aria-current="{{ $ariaCurrent }}" @endif
    {{ $attributes->merge(['class' => 'text-blue-600 dark:text-blue-400 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded']) }}
>
    {{ $slot }}
    
    @if($isExternal)
        <span class="sr-only">{{ __('a11y.labels.external_link') }}</span>
        <x-accessibility.icon 
            name="external-link" 
            size="xs" 
            :decorative="true"
            class="inline-block ml-1"
        />
    @endif
</a>
