<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Delete Account') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-red-600 dark:text-red-400 mb-2">
                            Warning: This action cannot be undone
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Deleting your account will permanently remove all your personal data and anonymize your content. 
                            This includes:
                        </p>
                        <ul class="mt-3 list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>Your profile information (name, email, bio, avatar)</li>
                            <li>All your comments</li>
                            <li>All your bookmarks and reactions</li>
                            <li>All uploaded media files</li>
                            <li>Your posts will be kept but anonymized</li>
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('gdpr.delete-account') }}" class="space-y-6">
                        @csrf
                        @method('DELETE')

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Confirm your password
                            </label>
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                            @error('password')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input 
                                    type="checkbox" 
                                    name="confirm_deletion" 
                                    id="confirm_deletion" 
                                    value="1"
                                    required
                                    class="rounded border-gray-300 dark:border-gray-700 text-blue-600 focus:ring-blue-500"
                                >
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="confirm_deletion" class="font-medium text-gray-700 dark:text-gray-300">
                                    I understand that this action is permanent and cannot be undone
                                </label>
                            </div>
                        </div>
                        @error('confirm_deletion')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a 
                                href="{{ route('dashboard') }}" 
                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                            >
                                Cancel
                            </a>
                            <button 
                                type="submit" 
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors"
                            >
                                Delete My Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
