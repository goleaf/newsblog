<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data" x-data="{ previewUrl: '{{ $user->avatar_url }}' }">
        @csrf
        @method('patch')

        <!-- Avatar Upload -->
        <div>
            <x-input-label for="avatar" :value="__('Profile Picture')" />
            <div class="mt-2 flex items-center space-x-4">
                <img 
                    :src="previewUrl" 
                    alt="{{ $user->name }}" 
                    class="w-20 h-20 rounded-full object-cover border-2 border-gray-300 dark:border-gray-600"
                >
                <div>
                    <input 
                        type="file" 
                        id="avatar" 
                        name="avatar" 
                        accept="image/*"
                        class="hidden"
                        @change="
                            const file = $event.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = (e) => previewUrl = e.target.result;
                                reader.readAsDataURL(file);
                            }
                        "
                    />
                    <label 
                        for="avatar" 
                        class="cursor-pointer inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Choose Photo
                    </label>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        JPG, PNG or GIF (max. 2MB)
                    </p>
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <!-- Bio Field -->
        <div>
            <x-input-label for="bio" :value="__('Bio')" />
            <textarea 
                id="bio" 
                name="bio" 
                rows="4" 
                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                placeholder="Tell us a bit about yourself..."
            >{{ old('bio', $user->bio) }}</textarea>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Brief description for your profile. Max 500 characters.
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('bio')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <!-- Website -->
        <div>
            <x-input-label for="website" :value="__('Website')" />
            <x-text-input 
                id="website" 
                name="website" 
                type="url" 
                class="mt-1 block w-full" 
                :value="old('website', $user->profile->website ?? '')" 
                placeholder="https://example.com"
            />
            <x-input-error class="mt-2" :messages="$errors->get('website')" />
        </div>

        <!-- Location -->
        <div>
            <x-input-label for="location" :value="__('Location')" />
            <x-text-input 
                id="location" 
                name="location" 
                type="text" 
                class="mt-1 block w-full" 
                :value="old('location', $user->profile->location ?? '')" 
                placeholder="City, Country"
            />
            <x-input-error class="mt-2" :messages="$errors->get('location')" />
        </div>

        <!-- Social Links -->
        <div class="space-y-4">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Social Links') }}</h3>
            
            <div>
                <x-input-label for="twitter" :value="__('Twitter/X Username')" />
                <div class="mt-1 flex rounded-md shadow-sm">
                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-sm">
                        @
                    </span>
                    <x-text-input 
                        id="twitter" 
                        name="social_links[twitter]" 
                        type="text" 
                        class="flex-1 rounded-none rounded-r-md" 
                        :value="old('social_links.twitter', $user->profile->social_links['twitter'] ?? '')" 
                        placeholder="username"
                    />
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('social_links.twitter')" />
            </div>

            <div>
                <x-input-label for="github" :value="__('GitHub Username')" />
                <div class="mt-1 flex rounded-md shadow-sm">
                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-sm">
                        github.com/
                    </span>
                    <x-text-input 
                        id="github" 
                        name="social_links[github]" 
                        type="text" 
                        class="flex-1 rounded-none rounded-r-md" 
                        :value="old('social_links.github', $user->profile->social_links['github'] ?? '')" 
                        placeholder="username"
                    />
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('social_links.github')" />
            </div>

            <div>
                <x-input-label for="linkedin" :value="__('LinkedIn URL')" />
                <x-text-input 
                    id="linkedin" 
                    name="social_links[linkedin]" 
                    type="url" 
                    class="mt-1 block w-full" 
                    :value="old('social_links.linkedin', $user->profile->social_links['linkedin'] ?? '')" 
                    placeholder="https://linkedin.com/in/username"
                />
                <x-input-error class="mt-2" :messages="$errors->get('social_links.linkedin')" />
            </div>
        </div>

        <div class="hidden">
            <x-input-label for="email_hidden" :value="__('Email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
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
