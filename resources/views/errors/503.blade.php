<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Maintenance - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
    <div class="min-h-full flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <!-- Icon -->
            <div class="flex justify-center">
                <div class="relative">
                    <svg class="w-24 h-24 text-indigo-600 dark:text-indigo-400 animate-pulse-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-32 h-32 border-4 border-indigo-200 dark:border-indigo-800 rounded-full animate-ping opacity-20"></div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="space-y-4">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white">
                    We'll be back soon!
                </h1>
                
                <div class="space-y-2">
                    <p class="text-lg text-gray-600 dark:text-gray-300">
                        @if(isset($exception) && method_exists($exception, 'getMessage') && $exception->getMessage())
                            {{ $exception->getMessage() }}
                        @else
                            We're performing scheduled maintenance to improve your experience.
                        @endif
                    </p>
                    
                    @if(isset($retryAfter) && $retryAfter)
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Expected to be back in approximately {{ $retryAfter }} seconds.
                        </p>
                    @endif
                </div>

                <!-- Progress bar -->
                <div class="mt-8">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                        <div class="bg-indigo-600 dark:bg-indigo-400 h-2 rounded-full animate-pulse" style="width: 60%"></div>
                    </div>
                </div>

                <!-- Additional info -->
                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Thank you for your patience. We appreciate your understanding.
                    </p>
                    
                    @if(config('app.support_email'))
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                            Questions? Contact us at 
                            <a href="mailto:{{ config('app.support_email') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ config('app.support_email') }}
                            </a>
                        </p>
                    @endif
                </div>
            </div>

            <!-- Auto-refresh notice -->
            <div class="mt-8">
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    This page will automatically refresh in <span id="countdown">30</span> seconds
                </p>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh countdown
        let seconds = 30;
        const countdownElement = document.getElementById('countdown');
        
        const countdown = setInterval(() => {
            seconds--;
            if (countdownElement) {
                countdownElement.textContent = seconds;
            }
            
            if (seconds <= 0) {
                clearInterval(countdown);
                window.location.reload();
            }
        }, 1000);
    </script>
</body>
</html>
