@php
    $containerClasses = $class ?? 'flex gap-4 items-center';
    $itemClasses = $itemClass ?? 'relative';
    $linkClasses = $linkClass ?? 'text-sm font-medium hover:text-primary-600 dark:hover:text-primary-400';
@endphp

@if($menu)
    @if($location->value === 'mobile')
        <div class="md:hidden" data-menu-mobile>
            <button type="button" class="inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm"
                    data-menu-toggle aria-expanded="false" aria-controls="mobile-menu">
                {{ __('Menu') }}
            </button>
            <div id="mobile-menu" class="hidden mt-2 flex flex-col gap-2" data-menu-panel>
                @include('components.menu-items', ['items' => $menu->rootItems, 'itemClasses' => 'py-2', 'linkClasses' => 'block ' . $linkClasses])
            </div>
        </div>
    @else
        <nav class="{{ $containerClasses }}" aria-label="{{ __('Navigation') }}">
            @include('components.menu-items', ['items' => $menu->rootItems, 'itemClasses' => $itemClasses, 'linkClasses' => $linkClasses])
        </nav>
    @endif
@endif


