<x-app-layout>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ __('Notification Preferences') }}
                        </h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Manage how and when you receive notifications.') }}
                        </p>
                    </div>

                    <form action="{{ route('notifications.preferences.update') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Email Notification Settings -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                {{ __('Email Notifications') }}
                            </h3>
                            
                            <div class="space-y-4">
                                <!-- Comment Replies -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label for="comment_replies" class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ __('Comment Replies') }}
                                        </label>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ __('Get notified when someone replies to your comment') }}
                                        </p>
                                    </div>
                                    <input 
                                        type="checkbox" 
                                        id="comment_replies" 
                                        name="comment_replies" 
                                        value="1"
                                        {{ ($preferences['comment_replies'] ?? true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                </div>

                                <!-- New Followers -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label for="new_followers" class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ __('New Followers') }}
                                        </label>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ __('Get notified when someone follows you') }}
                                        </p>
                                    </div>
                                    <input 
                                        type="checkbox" 
                                        id="new_followers" 
                                        name="new_followers" 
                                        value="1"
                                        {{ ($preferences['new_followers'] ?? true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                </div>

                                <!-- Author New Article -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label for="author_new_article" class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ __('New Articles from Authors') }}
                                        </label>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ __('Get notified when authors you follow publish new articles') }}
                                        </p>
                                    </div>
                                    <input 
                                        type="checkbox" 
                                        id="author_new_article" 
                                        name="author_new_article" 
                                        value="1"
                                        {{ ($preferences['author_new_article'] ?? true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                </div>

                                <!-- Comment Reactions -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label for="comment_reactions" class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ __('Comment Reactions') }}
                                        </label>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ __('Get notified when someone reacts to your comment') }}
                                        </p>
                                    </div>
                                    <input 
                                        type="checkbox" 
                                        id="comment_reactions" 
                                        name="comment_reactions" 
                                        value="1"
                                        {{ ($preferences['comment_reactions'] ?? true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                </div>

                                <!-- Mentions -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label for="mentions" class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ __('Mentions') }}
                                        </label>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ __('Get notified when someone mentions you') }}
                                        </p>
                                    </div>
                                    <input 
                                        type="checkbox" 
                                        id="mentions" 
                                        name="mentions" 
                                        value="1"
                                        {{ ($preferences['mentions'] ?? true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                </div>

                                <!-- Comment Approved -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label for="comment_approved" class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ __('Comment Approved') }}
                                        </label>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ __('Get notified when your comment is approved') }}
                                        </p>
                                    </div>
                                    <input 
                                        type="checkbox" 
                                        id="comment_approved" 
                                        name="comment_approved" 
                                        value="1"
                                        {{ ($preferences['comment_approved'] ?? true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                </div>

                                <!-- Post Published -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label for="post_published" class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ __('Post Published') }}
                                        </label>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ __('Get notified when your post is published') }}
                                        </p>
                                    </div>
                                    <input 
                                        type="checkbox" 
                                        id="post_published" 
                                        name="post_published" 
                                        value="1"
                                        {{ ($preferences['post_published'] ?? true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                </div>

                                <!-- Newsletter -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label for="newsletter" class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ __('Newsletter') }}
                                        </label>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ __('Receive our newsletter with curated content') }}
                                        </p>
                                    </div>
                                    <input 
                                        type="checkbox" 
                                        id="newsletter" 
                                        name="newsletter" 
                                        value="1"
                                        {{ ($preferences['newsletter'] ?? true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Notification Frequency -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                {{ __('Notification Frequency') }}
                            </h3>
                            
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input 
                                        type="radio" 
                                        name="frequency" 
                                        value="immediate"
                                        {{ ($preferences['frequency'] ?? 'immediate') === 'immediate' ? 'checked' : '' }}
                                        class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                    <span class="ml-3">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Immediate') }}</span>
                                        <span class="block text-xs text-gray-600 dark:text-gray-400">{{ __('Get notified as soon as something happens') }}</span>
                                    </span>
                                </label>

                                <label class="flex items-center">
                                    <input 
                                        type="radio" 
                                        name="frequency" 
                                        value="daily"
                                        {{ ($preferences['frequency'] ?? 'immediate') === 'daily' ? 'checked' : '' }}
                                        class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                    <span class="ml-3">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Daily Digest') }}</span>
                                        <span class="block text-xs text-gray-600 dark:text-gray-400">{{ __('Receive a daily summary of notifications') }}</span>
                                    </span>
                                </label>

                                <label class="flex items-center">
                                    <input 
                                        type="radio" 
                                        name="frequency" 
                                        value="weekly"
                                        {{ ($preferences['frequency'] ?? 'immediate') === 'weekly' ? 'checked' : '' }}
                                        class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                    <span class="ml-3">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Weekly Digest') }}</span>
                                        <span class="block text-xs text-gray-600 dark:text-gray-400">{{ __('Receive a weekly summary of notifications') }}</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a 
                                href="{{ route('notifications.index') }}"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                            >
                                {{ __('Cancel') }}
                            </a>
                            <button 
                                type="submit"
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                {{ __('Save Preferences') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
