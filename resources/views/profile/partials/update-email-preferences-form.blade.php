<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Email Preferences') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Manage your email notification preferences.') }}
        </p>
    </header>

    <form 
        method="post" 
        action="{{ route('profile.email-preferences') }}" 
        class="mt-6 space-y-6"
        x-data="{ 
            preferences: {{ json_encode($user->getEmailPreferences()) }},
            frequency: '{{ $user->getEmailPreferences()['frequency'] ?? 'immediate' }}'
        }"
    >
        @csrf
        @method('patch')

        <!-- Notification Types -->
        <div class="space-y-4">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                Receive notifications for:
            </h3>

            <!-- Comment Replies -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="comment_replies"
                    name="preferences[comment_replies]"
                    value="1"
                    x-model="preferences.comment_replies"
                    class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-800"
                    {{ $user->getEmailPreferences()['comment_replies'] ? 'checked' : '' }}
                >
                <label for="comment_replies" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-medium">Comment Replies</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        When someone replies to your comment
                    </span>
                </label>
            </div>

            <!-- Post Published -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="post_published"
                    name="preferences[post_published]"
                    value="1"
                    x-model="preferences.post_published"
                    class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-800"
                    {{ $user->getEmailPreferences()['post_published'] ? 'checked' : '' }}
                >
                <label for="post_published" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-medium">New Articles</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        When new articles are published
                    </span>
                </label>
            </div>

            <!-- Comment Approved -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="comment_approved"
                    name="preferences[comment_approved]"
                    value="1"
                    x-model="preferences.comment_approved"
                    class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-800"
                    {{ $user->getEmailPreferences()['comment_approved'] ? 'checked' : '' }}
                >
                <label for="comment_approved" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-medium">Comment Approved</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        When your comment is approved
                    </span>
                </label>
            </div>

            <!-- Series Updated -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="series_updated"
                    name="preferences[series_updated]"
                    value="1"
                    x-model="preferences.series_updated"
                    class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-800"
                    {{ $user->getEmailPreferences()['series_updated'] ? 'checked' : '' }}
                >
                <label for="series_updated" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-medium">Series Updates</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        When a series you're following is updated
                    </span>
                </label>
            </div>

            <!-- Newsletter -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="newsletter"
                    name="preferences[newsletter]"
                    value="1"
                    x-model="preferences.newsletter"
                    class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-800"
                    {{ $user->getEmailPreferences()['newsletter'] ? 'checked' : '' }}
                >
                <label for="newsletter" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-medium">Newsletter</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        Receive our weekly newsletter
                    </span>
                </label>
            </div>
        </div>

        <!-- Frequency -->
        <div class="space-y-3">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                Email Frequency
            </h3>

            <div class="space-y-2">
                <div class="flex items-center">
                    <input 
                        type="radio" 
                        id="frequency_immediate"
                        name="preferences[frequency]"
                        value="immediate"
                        x-model="frequency"
                        class="border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-800"
                        {{ $user->getEmailPreferences()['frequency'] === 'immediate' ? 'checked' : '' }}
                    >
                    <label for="frequency_immediate" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                        <span class="font-medium">Immediate</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            Receive emails as notifications occur
                        </span>
                    </label>
                </div>

                <div class="flex items-center">
                    <input 
                        type="radio" 
                        id="frequency_daily"
                        name="preferences[frequency]"
                        value="daily"
                        x-model="frequency"
                        class="border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-800"
                        {{ $user->getEmailPreferences()['frequency'] === 'daily' ? 'checked' : '' }}
                    >
                    <label for="frequency_daily" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                        <span class="font-medium">Daily Digest</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            Receive a daily summary of notifications
                        </span>
                    </label>
                </div>

                <div class="flex items-center">
                    <input 
                        type="radio" 
                        id="frequency_weekly"
                        name="preferences[frequency]"
                        value="weekly"
                        x-model="frequency"
                        class="border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-800"
                        {{ $user->getEmailPreferences()['frequency'] === 'weekly' ? 'checked' : '' }}
                    >
                    <label for="frequency_weekly" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                        <span class="font-medium">Weekly Digest</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            Receive a weekly summary of notifications
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save Preferences') }}</x-primary-button>

            @if (session('status') === 'email-preferences-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
