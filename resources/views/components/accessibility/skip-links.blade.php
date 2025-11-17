@props(['links' => null])

@php
    $skipLinks = $links ?? [
        ['target' => '#main-content', 'label' => __('a11y.skip_to_main')],
        ['target' => '#navigation', 'label' => __('a11y.skip_to_navigation')],
        ['target' => '#footer', 'label' => __('a11y.skip_to_footer')],
    ];
@endphp

<nav aria-label="{{ __('a11y.navigation.skip_links') }}" class="skip-links-container">
    @foreach($skipLinks as $link)
        <a 
            href="{{ $link['target'] }}" 
            class="skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-blue-600 focus:text-white focus:rounded-md focus:shadow-lg"
        >
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
