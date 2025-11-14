@extends('layouts.app')

@section('title', 'Privacy Settings')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">
            Privacy Settings
        </h1>

        <div class="space-y-6">
            {{-- Cookie Consent --}}
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                    Cookie Consent
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Manage your cookie preferences. You can change your consent at any time.
                </p>
                
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">
                            Current Status: 
                            <span id="consent-status" class="text-indigo-600 dark:text-indigo-400">
                                {{ request()->cookie('gdpr_consent') === 'accepted' ? 'Accepted' : 'Not Set' }}
                            </span>
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <form action="{{ route('gdpr.withdraw-consent') }}" method="POST">
                            @csrf
                            <button 
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            >
                                Withdraw Consent
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Do Not Track --}}
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                    Do Not Track
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    We respect the Do Not Track (DNT) browser setting. When enabled, we will not track your activity.
                </p>
                
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">
                            Browser DNT Status: 
                            <span id="dnt-status" class="text-indigo-600 dark:text-indigo-400">
                                {{ request()->header('DNT') === '1' ? 'Enabled' : 'Disabled' }}
                            </span>
                        </p>
                    </div>
                    <a 
                        href="https://allaboutdnt.com/" 
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                    >
                        Learn how to enable DNT
                    </a>
                </div>
            </div>

            @auth
            {{-- Data Export --}}
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                    Export Your Data
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Download a copy of all your personal data we have stored.
                </p>
                
                <a 
                    href="{{ route('gdpr.export-data') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Download My Data
                </a>
            </div>

            {{-- Account Deletion --}}
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                    Delete Account
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Permanently delete your account and all associated data. This action cannot be undone.
                </p>
                
                <a 
                    href="{{ route('gdpr.show-delete-account') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete My Account
                </a>
            </div>
            @endauth
        </div>
    </div>
</div>
@endsection
