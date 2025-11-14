@props([
    'position' => 'top-right' // top-right, top-left, bottom-right, bottom-left, top-center, bottom-center
])

@php
    $positionClasses = [
        'top-right' => 'top-4 right-4',
        'top-left' => 'top-4 left-4',
        'bottom-right' => 'bottom-4 right-4',
        'bottom-left' => 'bottom-4 left-4',
        'top-center' => 'top-4 left-1/2 -translate-x-1/2',
        'bottom-center' => 'bottom-4 left-1/2 -translate-x-1/2'
    ];
    
    $positionClass = $positionClasses[$position] ?? $positionClasses['top-right'];
@endphp

<div 
    x-data="{
        notifications: $store.notifications.notifications,
        getIcon(type) {
            const icons = {
                success: '<path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z\' />',
                error: '<path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z\' />',
                warning: '<path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z\' />',
                info: '<path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z\' />'
            };
            return icons[type] || icons.info;
        },
        getColors(type) {
            const colors = {
                success: {
                    bg: 'bg-green-50 dark:bg-green-900/20',
                    border: 'border-green-200 dark:border-green-800',
                    text: 'text-green-800 dark:text-green-200',
                    icon: 'text-green-400 dark:text-green-500'
                },
                error: {
                    bg: 'bg-red-50 dark:bg-red-900/20',
                    border: 'border-red-200 dark:border-red-800',
                    text: 'text-red-800 dark:text-red-200',
                    icon: 'text-red-400 dark:text-red-500'
                },
                warning: {
                    bg: 'bg-yellow-50 dark:bg-yellow-900/20',
                    border: 'border-yellow-200 dark:border-yellow-800',
                    text: 'text-yellow-800 dark:text-yellow-200',
                    icon: 'text-yellow-400 dark:text-yellow-500'
                },
                info: {
                    bg: 'bg-blue-50 dark:bg-blue-900/20',
                    border: 'border-blue-200 dark:border-blue-800',
                    text: 'text-blue-800 dark:text-blue-200',
                    icon: 'text-blue-400 dark:text-blue-500'
                }
            };
            return colors[type] || colors.info;
        }
    }"
    class="fixed {{ $positionClass }} z-50 space-y-2 pointer-events-none"
    aria-live="polite"
    aria-atomic="true"
>
    <template x-for="notification in notifications" :key="notification.id">
        <div 
            x-show="notification.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-2"
            :class="getColors(notification.type).bg + ' ' + getColors(notification.type).border"
            class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg border shadow-lg"
            role="alert"
        >
            <div class="p-4">
                <div class="flex items-start">
                    {{-- Icon --}}
                    <div class="flex-shrink-0">
                        <svg 
                            :class="getColors(notification.type).icon"
                            class="h-6 w-6" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke-width="1.5" 
                            stroke="currentColor" 
                            aria-hidden="true"
                            x-html="getIcon(notification.type)"
                        ></svg>
                    </div>
                    
                    {{-- Message --}}
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p 
                            :class="getColors(notification.type).text"
                            class="text-sm font-medium"
                            x-text="notification.message"
                        ></p>
                    </div>
                    
                    {{-- Close button --}}
                    <div class="ml-4 flex flex-shrink-0">
                        <button 
                            type="button"
                            @click="$store.notifications.dismiss(notification.id)"
                            :class="getColors(notification.type).text"
                            class="inline-flex rounded-md hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            aria-label="Close notification"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
