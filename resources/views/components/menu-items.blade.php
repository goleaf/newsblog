@php
    /** @var \Illuminate\Database\Eloquent\Collection|\App\Models\MenuItem[] $items */
    $currentUrl = url()->current();
@endphp

@foreach($items as $item)
    @php
        $isActive = $item->url && (\Illuminate\Support\Str::of($currentUrl)->rtrim('/') === \Illuminate\Support\Str::of(url($item->url))->rtrim('/'));
    @endphp
    <div class="{{ $itemClasses ?? '' }}">
        <a href="{{ $item->url ?? '#' }}"
           @class([$linkClasses ?? '', 'text-primary-600 dark:text-primary-400' => $isActive])
           @if($item->target) target="{{ $item->target }}" rel="{{ $item->target === '_blank' ? 'noopener noreferrer' : '' }}" @endif
        >
            {{ $item->title }}
        </a>
        @if($item->children->isNotEmpty())
            <div class="ml-4 mt-2 flex flex-col gap-2">
                @include('components.menu-items', ['items' => $item->children, 'itemClasses' => $itemClasses ?? '', 'linkClasses' => $linkClasses ?? ''])
            </div>
        @endif
    </div>
@endforeach


