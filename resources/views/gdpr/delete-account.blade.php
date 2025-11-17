<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Delete Account') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6">
                        <h3 class="mb-4 text-lg font-medium">Delete Your Account</h3>
                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            Once your account is deleted, all of your data will be permanently removed. Your posts will remain but will be anonymized.
                        </p>
                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            Before deleting your account, you may want to download your data using the export feature below.
                        </p>
                    </div>

                    <div class="mb-8">
                        <h4 class="mb-2 text-base font-medium">Export Your Data</h4>
                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            Download a copy of all your data in JSON format.
                        </p>
                        <a href="{{ route('gdpr.export-data') }}" 
                           class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:bg-blue-900 dark:bg-blue-200 dark:text-blue-900 dark:hover:bg-white dark:focus:bg-white dark:focus:ring-offset-gray-800 dark:active:bg-gray-300">
                            Export Data
                        </a>
                    </div>

                    <div class="border-t border-gray-200 pt-8 dark:border-gray-700">
                        <h4 class="mb-2 text-base font-medium text-red-600 dark:text-red-400">Danger Zone</h4>
                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            This action cannot be undone. Please be certain.
                        </p>

                        <form method="POST" action="{{ route('gdpr.delete-account') }}" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')

                            <div class="mb-4">
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Confirm your password
                                </label>
                                <input type="password" 
                                       name="password" 
                                       id="password" 
                                       required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-blue-600 dark:focus:ring-blue-600 sm:text-sm">
                                @error('password')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" 
                                    class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-red-700 focus:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-900 dark:focus:ring-offset-gray-800">
                                Delete Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
