<div 
    x-data="{ 
        show: false,
        init() {
            // Check if consent has been given
            const consent = this.getCookie('gdpr_consent');
            if (!consent) {
                this.show = true;
            }
        },
        getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        },
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
    x-transition:enter-start="opacity-0 transform translate-y-4"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-4"
    class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-lg"
    style="display: none;"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                <p>
                    We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. 
                    By clicking "Accept", you consent to our use of cookies. 
                    <a href="{{ route('gdpr.privacy-policy') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                        Learn more
                    </a>
                </p>
            </div>
            <div class="flex gap-3">
                <button 
                    @click="decline"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                >
                    Decline
                </button>
                <button 
                    @click="accept"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                >
                    Accept
                </button>
            </div>
        </div>
    </div>
</div>
