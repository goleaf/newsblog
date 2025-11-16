<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Privacy & Preferences') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Manage your privacy settings and display preferences.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.preferences.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- Theme Preference -->
        <div>
            <x-input-label for="theme" :value="__('Theme')" />
            <select 
                id="theme" 
                name="preferences[theme]" 
                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
            >
                <option value="auto" {{ old('preferences.theme', $user->preferences->preferences['theme'] ?? 'auto') === 'auto' ? 'selected' : '' }}>
                    Auto (System)
                </option>
                <option value="light" {{ old('preferences.theme', $user->preferences->preferences['theme'] ?? 'auto') === 'light' ? 'selected' : '' }}>
                    Light
                </option>
                <option value="dark" {{ old('preferences.theme', $user->preferences->preferences['theme'] ?? 'auto') === 'dark' ? 'selected' : '' }}>
                    Dark
                </option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('preferences.theme')" />
        </div>

        <!-- Profile Visibility -->
        <div>
            <x-input-label for="profile_visibility" :value="__('Profile Visibility')" />
            <select 
                id="profile_visibility" 
                name="preferences[profile_visibility]" 
                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
            >
                <option value="public" {{ old('preferences.profile_visibility', $user->preferences->preferences['profile_visibility'] ?? 'public') === 'public' ? 'selected' : '' }}>
                    Public - Anyone can view your profile
                </option>
                <option value="followers" {{ old('preferences.profile_visibility', $user->preferences->preferences['profile_visibility'] ?? 'public') === 'followers' ? 'selected' : '' }}>
                    Followers Only - Only people you follow can view
                </option>
                <option value="private" {{ old('preferences.profile_visibility', $user->preferences->preferences['profile_visibility'] ?? 'public') === 'private' ? 'selected' : '' }}>
                    Private - Only you can view your profile
                </option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('preferences.profile_visibility')" />
        </div>

        <!-- Reading List Visibility -->
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input 
                    id="reading_list_public" 
                    name="preferences[reading_list_public]" 
                    type="checkbox" 
                    value="1"
                    {{ old('preferences.reading_list_public', $user->preferences->preferences['reading_list_public'] ?? false) ? 'checked' : '' }}
                    class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-800"
                />
            </div>
            <div class="ml-3 text-sm">
                <label for="reading_list_public" class="font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Make reading lists public') }}
                </label>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('Allow others to view your reading lists and bookmarks.') }}
                </p>
            </div>
        </div>

        <!-- Show Email -->
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input 
                    id="show_email" 
                    name="preferences[show_email]" 
                    type="checkbox" 
                    value="1"
                    {{ old('preferences.show_email', $user->preferences->preferences['show_email'] ?? false) ? 'checked' : '' }}
                    class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-800"
                />
            </div>
            <div class="ml-3 text-sm">
                <label for="show_email" class="font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Show email on profile') }}
                </label>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('Display your email address on your public profile.') }}
                </p>
            </div>
        </div>

        <!-- Show Location -->
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input 
                    id="show_location" 
                    name="preferences[show_location]" 
                    type="checkbox" 
                    value="1"
                    {{ old('preferences.show_location', $user->preferences->preferences['show_location'] ?? true) ? 'checked' : '' }}
                    class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:focus:ring-offset-gray-800"
                />
            </div>
            <div class="ml-3 text-sm">
                <label for="show_location" class="font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Show location on profile') }}
                </label>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('Display your location on your public profile.') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save Preferences') }}</x-primary-button>

            @if (session('status') === 'preferences-updated')
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
