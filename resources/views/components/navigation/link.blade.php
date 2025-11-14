@props([
    'href',
    'active' => false,
    'icon' => null,
])

<a 
    href="{{ $href }}" 
    {{ $attributes->merge([
        'class' => $active 
            ? 'text-blue-600 dark:text-blue-400 font-medium' 
            : 'text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors font-medium'
    ]) }}
    aria-current="{{ $active ? 'page' : 'false' }}"
>
    @if($icon)
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            {!! $icon !!}
        </svg>
    @endif
    {{ $slot }}
</a>
