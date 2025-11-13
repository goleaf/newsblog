<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <h1 class="text-9xl font-extrabold text-gray-900 dark:text-white">429</h1>
                <h2 class="mt-6 text-3xl font-bold text-gray-900 dark:text-white">
                    Too Many Requests
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    You have made too many requests. Please slow down and try again later.
                </p>
                
                @if(isset($retry_after))
                    <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            Please wait <strong>{{ $retry_after }}</strong> seconds before trying again.
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-8">
                <a href="{{ route('home') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Return to Home
                </a>
            </div>

            <div class="mt-6">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    If you believe this is an error, please contact support.
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
