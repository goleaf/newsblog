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
                <div class="h-48 bg-gray-300 dark:bg-gray-700"></div>
                
                {{-- Content placeholder --}}
                <div class="p-6 space-y-4">
                    {{-- Title --}}
                    <div class="h-6 bg-gray-300 dark:bg-gray-700 rounded w-3/4"></div>
                    
                    {{-- Excerpt lines --}}
                    <div class="space-y-2">
                        <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded"></div>
                        <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-5/6"></div>
                    </div>
                    
                    {{-- Meta info --}}
                    <div class="flex items-center justify-between">
                        <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-1/4"></div>
                        <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-1/4"></div>
                    </div>
                </div>
            </div>
        @endfor
    @elseif($type === 'list')
        @for($i = 0; $i < $count; $i++)
            <div class="flex gap-4 mb-4">
                {{-- Thumbnail --}}
                <div class="w-24 h-24 bg-gray-300 dark:bg-gray-700 rounded flex-shrink-0"></div>
                
                {{-- Content --}}
                <div class="flex-1 space-y-2">
                    <div class="h-5 bg-gray-300 dark:bg-gray-700 rounded w-3/4"></div>
                    <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-full"></div>
                    <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-1/3"></div>
                </div>
            </div>
        @endfor
    @elseif($type === 'text')
        @for($i = 0; $i < $count; $i++)
            <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded mb-2 {{ $i === $count - 1 ? 'w-2/3' : '' }}"></div>
        @endfor
    @elseif($type === 'image')
        <div class="w-full h-64 bg-gray-300 dark:bg-gray-700 rounded"></div>
    @elseif($type === 'avatar')
        <div class="w-10 h-10 bg-gray-300 dark:bg-gray-700 rounded-full"></div>
    @endif
</div>
