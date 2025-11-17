@props(['title' => 'Subscribe to Our Newsletter', 'description' => 'Get the latest articles and updates delivered to your inbox.'])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg shadow-md p-6']) }}>
    <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-gray-100">{{ $title }}</h3>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $description }}</p>
    
    @if (session('success'))
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/20 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if (session('info'))
        <div class="mb-4 rounded-md bg-blue-50 dark:bg-blue-900/20 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ session('info') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
    @endif
    
    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label for="newsletter-email-{{ uniqid() }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Email Address
            </label>
            <input type="email" 
                   id="newsletter-email-{{ uniqid() }}"
                   name="email" 
                   placeholder="your@email.com" 
                   required
                   value="{{ old('email') }}"
                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 @error('email') border-red-500 @enderror">
            @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-start">
            <input type="checkbox" 
                   id="newsletter-gdpr-{{ uniqid() }}"
                   name="gdpr_consent" 
                   required
                   class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded @error('gdpr_consent') border-red-500 @enderror">
            <label for="newsletter-gdpr-{{ uniqid() }}" class="ml-2 text-xs text-gray-600 dark:text-gray-400">
                I agree to receive newsletters and accept the <a href="{{ route('gdpr.privacy-policy') }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 underline">privacy policy</a>
            </label>
        </div>
        @error('gdpr_consent')
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Subscribe Now
        </button>
    </form>
    
    <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
        We respect your privacy. Unsubscribe at any time.
    </p>
</div>
