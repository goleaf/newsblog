<div x-data="{ 
    show: !document.cookie.includes('cookie_consent='),
    accept() {
        fetch('{{ route('gdpr.accept-consent') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(() => {
            this.show = false;
        });
    },
    decline() {
        fetch('{{ route('gdpr.decline-consent') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(() => {
            this.show = false;
        });
    }
}" 
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-y-full"
     x-transition:enter-end="translate-y-0"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="translate-y-0"
     x-transition:leave-end="translate-y-full"
     class="fixed inset-x-0 bottom-0 z-50 pb-2 sm:pb-5"
     style="display: none;">
    <div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8">
        <div class="rounded-lg bg-blue-600 p-2 shadow-lg dark:bg-blue-800 sm:p-3">
            <div class="flex flex-wrap items-center justify-between">
                <div class="flex w-0 flex-1 items-center">
                    <span class="flex rounded-lg bg-blue-800 p-2 dark:bg-blue-900">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <p class="ml-3 truncate font-medium text-white">
                        <span class="md:hidden">We use cookies to improve your experience.</span>
                        <span class="hidden md:inline">
                            We use cookies to enhance your browsing experience and analyze our traffic. 
                            By clicking "Accept", you consent to our use of cookies.
                        </span>
                    </p>
                </div>
                <div class="order-3 mt-2 w-full flex-shrink-0 sm:order-2 sm:mt-0 sm:w-auto">
                    <a href="{{ route('gdpr.privacy-policy') }}" 
                       class="flex items-center justify-center rounded-md border border-transparent bg-white px-4 py-2 text-sm font-medium text-blue-600 shadow-sm hover:bg-blue-50 dark:bg-gray-800 dark:text-blue-400 dark:hover:bg-gray-700">
                        Learn more
                    </a>
                </div>
                <div class="order-2 flex flex-shrink-0 gap-2 sm:order-3 sm:ml-2">
                    <button @click="decline" 
                            type="button" 
                            class="flex items-center justify-center rounded-md border border-white px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 dark:hover:bg-blue-900">
                        Decline
                    </button>
                    <button @click="accept" 
                            type="button" 
                            class="flex items-center justify-center rounded-md border border-transparent bg-white px-4 py-2 text-sm font-medium text-blue-600 shadow-sm hover:bg-blue-50 dark:bg-gray-800 dark:text-blue-400 dark:hover:bg-gray-700">
                        Accept
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
