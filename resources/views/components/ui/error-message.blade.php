@props([
    'type' => 'error', // error, warning, info
    'title' => null,
    'message' => null,
    'dismissible' => false,
    'retryAction' => null,
    'retryText' => 'Try Again'
])

@php
    $typeConfig = [
        'error' => [
            'bg' => 'bg-red-50 dark:bg-red-900/20',
            'border' => 'border-red-200 dark:border-red-800',
            'text' => 'text-red-800 dark:text-red-200',
            'icon' => 'text-red-400 dark:text-red-500',
            'iconPath' => 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z'
        ],
        'warning' => [
            'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
            'border' => 'border-yellow-200 dark:border-yellow-800',
            'text' => 'text-yellow-800 dark:text-yellow-200',
            'icon' => 'text-yellow-400 dark:text-yellow-500',
            'iconPath' => 'M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z'
        ],
        'info' => [
            'bg' => 'bg-blue-50 dark:bg-blue-900/20',
            'border' => 'border-blue-200 dark:border-blue-800',
            'text' => 'text-blue-800 dark:text-blue-200',
            'icon' => 'text-blue-400 dark:text-blue-500',
            'iconPath' => 'M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v4.59L7.3 9.24a.75.75 0 00-1.1 1.02l3.25 3.5a.75.75 0 001.1 0l3.25-3.5a.75.75 0 10-1.1-1.02l-1.95 2.1V6.75z'
        ]
    ];
    
    $config = $typeConfig[$type] ?? $typeConfig['error'];
@endphp

<div 
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    {{ $attributes->merge(['class' => "rounded-lg border p-4 {$config['bg']} {$config['border']}"]) }}
    role="alert"
>
    <div class="flex">
        {{-- Icon --}}
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 {{ $config['icon'] }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="{{ $config['iconPath'] }}" clip-rule="evenodd" />
            </svg>
        </div>
        
        {{-- Content --}}
        <div class="ml-3 flex-1">
            @if($title)
                <h3 class="text-sm font-medium {{ $config['text'] }}">{{ $title }}</h3>
            @endif
            
            @if($message)
                <div class="text-sm {{ $config['text'] }} {{ $title ? 'mt-2' : '' }}">
                    {{ $message }}
                </div>
            @endif
            
            {{ $slot }}
            
            @if($retryAction)
                <div class="mt-4">
                    <button 
                        type="button"
                        @click="{{ $retryAction }}"
                        class="text-sm font-medium {{ $config['text'] }} hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-{{ $type === 'error' ? 'red' : ($type === 'warning' ? 'yellow' : 'blue') }}-500"
                    >
                        {{ $retryText }}
                    </button>
                </div>
            @endif
        </div>
        
        {{-- Dismiss button --}}
        @if($dismissible)
            <div class="ml-auto pl-3">
                <button 
                    type="button"
                    @click="show = false"
                    class="-mx-1.5 -my-1.5 rounded-lg p-1.5 {{ $config['text'] }} hover:bg-{{ $type === 'error' ? 'red' : ($type === 'warning' ? 'yellow' : 'blue') }}-100 dark:hover:bg-{{ $type === 'error' ? 'red' : ($type === 'warning' ? 'yellow' : 'blue') }}-900/40 focus:outline-none focus:ring-2 focus:ring-{{ $type === 'error' ? 'red' : ($type === 'warning' ? 'yellow' : 'blue') }}-500"
                    aria-label="Dismiss"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                </button>
            </div>
        @endif
    </div>
</div>
