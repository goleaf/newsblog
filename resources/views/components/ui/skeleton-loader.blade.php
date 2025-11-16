@props([
    'type' => 'card', // card, list, text, image, avatar
    'count' => 1,
    'class' => ''
])

<div {{ $attributes->merge(['class' => 'animate-pulse ' . $class]) }}>
    @if($type === 'card')
        @for($i = 0; $i < $count; $i++)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
                {{-- Image placeholder --}}
                <div class="h-48 skeleton"></div>
                
                {{-- Content placeholder --}}
                <div class="p-6 space-y-4">
                    {{-- Title --}}
                    <div class="h-6 skeleton rounded w-3/4"></div>
                    
                    {{-- Excerpt lines --}}
                    <div class="space-y-2">
                        <div class="h-4 skeleton rounded"></div>
                        <div class="h-4 skeleton rounded w-5/6"></div>
                    </div>
                    
                    {{-- Meta info --}}
                    <div class="flex items-center justify-between">
                        <div class="h-4 skeleton rounded w-1/4"></div>
                        <div class="h-4 skeleton rounded w-1/4"></div>
                    </div>
                </div>
            </div>
        @endfor
    @elseif($type === 'list')
        @for($i = 0; $i < $count; $i++)
            <div class="flex gap-4 mb-4">
                {{-- Thumbnail --}}
                <div class="w-24 h-24 skeleton rounded flex-shrink-0"></div>
                
                {{-- Content --}}
                <div class="flex-1 space-y-2">
                    <div class="h-5 skeleton rounded w-3/4"></div>
                    <div class="h-4 skeleton rounded w-full"></div>
                    <div class="h-4 skeleton rounded w-1/3"></div>
                </div>
            </div>
        @endfor
    @elseif($type === 'text')
        @for($i = 0; $i < $count; $i++)
            <div class="h-4 skeleton rounded mb-2 {{ $i === $count - 1 ? 'w-2/3' : '' }}"></div>
        @endfor
    @elseif($type === 'image')
        <div class="w-full h-64 skeleton rounded"></div>
    @elseif($type === 'avatar')
        <div class="w-10 h-10 skeleton rounded-full"></div>
    @endif
</div>
