{{-- Cookie Consent Banner - Requirement 16.4 --}}
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
        acceptConsent() {
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
        declineConsent() {
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
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed bottom-0 left-0 right-0 z-50 p-4 bg-white dark:bg-gray-800 border-t-2 border-indigo-600 shadow-lg"
    style="display: none;"
    role="dialog"
    aria-labelledby="cookie-consent-title"
    aria-describedby="cookie-consent-description"
>
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex-1">
                <h3 id="cookie-consent-title" class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    🍪 We use cookies
                </h3>
                <p id="cookie-consent-description" class="text-sm text-gray-600 dark:text-gray-400">
                    We use cookies and similar technologies to improve your experience, analyze site traffic, and personalize content. 
                    By clicking "Accept", you consent to our use of cookies. 
                    <a href="{{ route('gdpr.privacy-policy') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        Learn more
                    </a>
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <button
                    @click="declineConsent()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    Decline
                </button>
                <button
                    @click="acceptConsent()"
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors"
                >
                    Accept
                </button>
            </div>
        </div>
    </div>
</div>
