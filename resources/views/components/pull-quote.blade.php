@props([
    'text' => '',
    'attribution' => null,
    'align' => 'left', // left|right
])

@php
    $float = $align === 'right' ? 'md:float-right md:ml-6' : 'md:float-left md:mr-6';
@endphp

<figure class="my-6 max-w-md {{ $float }} md:w-1/2">
    <blockquote class="relative pl-6 border-l-4 border-primary-500 text-xl leading-relaxed font-semibold text-gray-900 dark:text-gray-100">
        <span class="absolute -left-3 -top-3 text-5xl text-primary-200 dark:text-primary-700 select-none">“</span>
        {{ $text }}
    </blockquote>
    @if ($attribution)
        <figcaption class="mt-2 text-sm text-gray-500 dark:text-gray-400">— {{ $attribution }}</figcaption>
    @endif
</figure>



