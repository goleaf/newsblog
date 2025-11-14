@props([
    'widget',
])

<div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 rounded-lg p-6">
    @if($widget->title)
    <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">
        {{ $widget->title }}
    </h3>
    @endif
    
    @if($widget->settings['description'] ?? null)
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        {{ $widget->settings['description'] }}
    </p>
    @endif
    
    <form 
        action="{{ route('newsletter.subscribe') }}" 
        method="POST"
        x-data="{ 
            submitting: false, 
            success: false, 
            error: null,
            email: '',
            gdprConsent: false
        }"
        @submit.prevent="
            if (!gdprConsent) {
                error = 'Please accept the privacy policy';
                return;
            }
            submitting = true;
            error = null;
            fetch($el.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: email, gdpr_consent: gdprConsent })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    success = true;
                    email = '';
                    gdprConsent = false;
                } else {
                    error = data.message || 'An error occurred';
                }
            })
            .catch(() => {
                error = 'An error occurred. Please try again.';
            })
            .finally(() => {
                submitting = false;
            });
        "
        class="space-y-3"
    >
        @csrf
        
        <!-- Success Message -->
        <div x-show="success" x-cloak class="p-3 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded text-sm">
            <p class="font-medium">Successfully subscribed!</p>
            <p class="text-xs mt-1">Please check your email to confirm your subscription.</p>
        </div>
        
        <!-- Error Message -->
        <div x-show="error" x-cloak class="p-3 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded text-sm" x-text="error"></div>
        
        <!-- Email Input -->
        <div x-show="!success">
            <label for="newsletter-email" class="sr-only">Email address</label>
            <input 
                type="email" 
                id="newsletter-email"
                name="email"
                x-model="email"
                required
                placeholder="Enter your email"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                :disabled="submitting"
            >
        </div>
        
        <!-- GDPR Consent -->
        <div x-show="!success" class="flex items-start gap-2">
            <input 
                type="checkbox" 
                id="newsletter-gdpr"
                name="gdpr_consent"
                x-model="gdprConsent"
                required
                class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-800"
                :disabled="submitting"
            >
            <label for="newsletter-gdpr" class="text-xs text-gray-600 dark:text-gray-400">
                I agree to receive newsletters and accept the 
                <a href="#" class="text-blue-600 dark:text-blue-400 hover:underline" target="_blank">
                    privacy policy
                </a>
            </label>
        </div>
        
        <!-- Submit Button -->
        <button 
            type="submit"
            x-show="!success"
            :disabled="submitting"
            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
        >
            <span x-show="!submitting">Subscribe</span>
            <span x-show="submitting" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Subscribing...</span>
            </span>
        </button>
    </form>
</div>
