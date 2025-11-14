@props([
    'icon' => null,
    'title' => 'No results found',
    'message' => null,
    'actionText' => null,
    'actionUrl' => null,
    'actionClick' => null
])

<div {{ $attributes->merge(['class' => 'text-center py-12']) }}>
    {{-- Icon --}}
    @if($icon)
        <div class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600">
            {{ $icon }}
        </div>
    @else
        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    @endif
    
    {{-- Title --}}
    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
    
    {{-- Message --}}
    @if($message)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $message }}</p>
    @endif
    
    {{-- Slot for custom content --}}
    @if($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
    
    {{-- Action button --}}
    @if($actionText)
        <div class="mt-6">
            @if($actionUrl)
                <a 
                    href="{{ $actionUrl }}"
                    class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    {{ $actionText }}
                </a>
            @elseif($actionClick)
                <button 
                    type="button"
                    @click="{{ $actionClick }}"
                    class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    {{ $actionText }}
                </button>
            @endif
        </div>
    @endif
</div>
