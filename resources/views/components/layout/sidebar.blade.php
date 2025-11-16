{{--
    Sidebar Component
    
    Displays a sidebar with widget areas. Can be used on any page that needs a sidebar.
    
    Usage:
    <x-layout.sidebar />
    or
    <x-layout.sidebar :sticky="true" />
--}}

@props([
    'sticky' => false,
    'slug' => 'sidebar',
])

<aside 
    {{ $attributes->merge(['class' => 'w-full lg:w-80 space-y-6' . ($sticky ? ' lg:sticky lg:top-24 lg:self-start' : '')]) }}
    role="complementary"
    aria-label="Sidebar"
>
    {{-- Widget Areas --}}
    <x-layout.widget-area :slug="$slug" />
    
    {{-- Additional Sidebar Widget Areas --}}
    @if($slug === 'sidebar')
        <x-layout.widget-area slug="sidebar-2" />
        <x-layout.widget-area slug="sidebar-3" />
    @endif
</aside>

