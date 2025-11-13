<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Settings Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-lg bg-green-50 p-4 text-green-800 dark:bg-green-900 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-800 dark:bg-red-900 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <div x-data="settingsManager()">
                        <!-- Tabs -->
                        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                @foreach($groups as $key => $label)
                                    <button @click="activeTab = '{{ $key }}'"
                                            :class="activeTab === '{{ $key }}' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                                            class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </nav>
                        </div>

                        <!-- Tab Content -->
                        @foreach($groups as $groupKey => $groupLabel)
                            <div x-show="activeTab === '{{ $groupKey }}'" x-cloak>
                                <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="group" value="{{ $groupKey }}">

                                    <div class="space-y-4">
                                        @php
                                            $groupSettings = $settings[$groupKey] ?? [];
                                        @endphp

                                        @if($groupKey === 'general')
                                            <div>
                                                <label class="block text-sm font-medium mb-2">Site Name</label>
                                                <input type="text" 
                                                       name="settings[site_name]" 
                                                       value="{{ $groupSettings['site_name'] ?? config('app.name') }}"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">Site Description</label>
                                                <textarea name="settings[site_description]" 
                                                          rows="3"
                                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">{{ $groupSettings['site_description'] ?? '' }}</textarea>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">Posts Per Page</label>
                                                <input type="number" 
                                                       name="settings[posts_per_page]" 
                                                       value="{{ $groupSettings['posts_per_page'] ?? 15 }}"
                                                       min="1"
                                                       max="100"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>
                                        @endif

                                        @if($groupKey === 'seo')
                                            <div>
                                                <label class="block text-sm font-medium mb-2">Meta Title</label>
                                                <input type="text" 
                                                       name="settings[meta_title]" 
                                                       value="{{ $groupSettings['meta_title'] ?? '' }}"
                                                       maxlength="60"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                                <p class="mt-1 text-xs text-gray-500">Recommended: 50-60 characters</p>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">Meta Description</label>
                                                <textarea name="settings[meta_description]" 
                                                          rows="3"
                                                          maxlength="160"
                                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">{{ $groupSettings['meta_description'] ?? '' }}</textarea>
                                                <p class="mt-1 text-xs text-gray-500">Recommended: 150-160 characters</p>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">Meta Keywords</label>
                                                <input type="text" 
                                                       name="settings[meta_keywords]" 
                                                       value="{{ $groupSettings['meta_keywords'] ?? '' }}"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                                <p class="mt-1 text-xs text-gray-500">Comma-separated keywords</p>
                                            </div>
                                        @endif

                                        @if($groupKey === 'social')
                                            <div>
                                                <label class="block text-sm font-medium mb-2">Facebook URL</label>
                                                <input type="url" 
                                                       name="settings[facebook_url]" 
                                                       value="{{ $groupSettings['facebook_url'] ?? '' }}"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">Twitter URL</label>
                                                <input type="url" 
                                                       name="settings[twitter_url]" 
                                                       value="{{ $groupSettings['twitter_url'] ?? '' }}"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">LinkedIn URL</label>
                                                <input type="url" 
                                                       name="settings[linkedin_url]" 
                                                       value="{{ $groupSettings['linkedin_url'] ?? '' }}"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">GitHub URL</label>
                                                <input type="url" 
                                                       name="settings[github_url]" 
                                                       value="{{ $groupSettings['github_url'] ?? '' }}"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>
                                        @endif

                                        @if($groupKey === 'email')
                                            <div>
                                                <label class="block text-sm font-medium mb-2">Admin Email</label>
                                                <input type="email" 
                                                       name="settings[admin_email]" 
                                                       value="{{ $groupSettings['admin_email'] ?? '' }}"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">From Name</label>
                                                <input type="text" 
                                                       name="settings[mail_from_name]" 
                                                       value="{{ $groupSettings['mail_from_name'] ?? config('mail.from.name') }}"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">From Email</label>
                                                <input type="email" 
                                                       name="settings[mail_from_address]" 
                                                       value="{{ $groupSettings['mail_from_address'] ?? config('mail.from.address') }}"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>

                                            <!-- Test Email Section -->
                                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                                                <h4 class="text-sm font-medium mb-3">Send Test Email</h4>
                                                <div class="flex gap-3">
                                                    <input type="email" 
                                                           x-model="testEmail"
                                                           placeholder="Enter email address"
                                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                                    <button type="button" 
                                                            @click="sendTestEmail()"
                                                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                                                        Send Test
                                                    </button>
                                                </div>
                                            </div>
                                        @endif

                                        @if($groupKey === 'comments')
                                            <div>
                                                <label class="flex items-center">
                                                    <input type="checkbox" 
                                                           name="settings[comments_enabled]" 
                                                           value="1"
                                                           {{ ($groupSettings['comments_enabled'] ?? '1') == '1' ? 'checked' : '' }}
                                                           class="rounded border-gray-300 dark:border-gray-600">
                                                    <span class="ml-2 text-sm">Enable Comments</span>
                                                </label>
                                            </div>

                                            <div>
                                                <label class="flex items-center">
                                                    <input type="checkbox" 
                                                           name="settings[comments_require_approval]" 
                                                           value="1"
                                                           {{ ($groupSettings['comments_require_approval'] ?? '1') == '1' ? 'checked' : '' }}
                                                           class="rounded border-gray-300 dark:border-gray-600">
                                                    <span class="ml-2 text-sm">Require Comment Approval</span>
                                                </label>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">Max Nesting Level</label>
                                                <input type="number" 
                                                       name="settings[comments_max_depth]" 
                                                       value="{{ $groupSettings['comments_max_depth'] ?? 3 }}"
                                                       min="1"
                                                       max="10"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>
                                        @endif

                                        @if($groupKey === 'media')
                                            <div>
                                                <label class="block text-sm font-medium mb-2">Max Upload Size (MB)</label>
                                                <input type="number" 
                                                       name="settings[max_upload_size]" 
                                                       value="{{ $groupSettings['max_upload_size'] ?? 10 }}"
                                                       min="1"
                                                       max="100"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">Allowed File Types</label>
                                                <input type="text" 
                                                       name="settings[allowed_file_types]" 
                                                       value="{{ $groupSettings['allowed_file_types'] ?? 'jpg,jpeg,png,gif,webp,pdf' }}"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                                <p class="mt-1 text-xs text-gray-500">Comma-separated file extensions</p>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">Image Quality (%)</label>
                                                <input type="number" 
                                                       name="settings[image_quality]" 
                                                       value="{{ $groupSettings['image_quality'] ?? 85 }}"
                                                       min="1"
                                                       max="100"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>
                                        @endif

                                        @if($groupKey === 'reading')
                                            <div>
                                                <label class="block text-sm font-medium mb-2">Words Per Minute</label>
                                                <input type="number" 
                                                       name="settings[reading_words_per_minute]" 
                                                       value="{{ $groupSettings['reading_words_per_minute'] ?? 200 }}"
                                                       min="100"
                                                       max="300"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                            </div>

                                            <div>
                                                <label class="flex items-center">
                                                    <input type="checkbox" 
                                                           name="settings[show_reading_time]" 
                                                           value="1"
                                                           {{ ($groupSettings['show_reading_time'] ?? '1') == '1' ? 'checked' : '' }}
                                                           class="rounded border-gray-300 dark:border-gray-600">
                                                    <span class="ml-2 text-sm">Show Reading Time</span>
                                                </label>
                                            </div>

                                            <div>
                                                <label class="flex items-center">
                                                    <input type="checkbox" 
                                                           name="settings[show_related_posts]" 
                                                           value="1"
                                                           {{ ($groupSettings['show_related_posts'] ?? '1') == '1' ? 'checked' : '' }}
                                                           class="rounded border-gray-300 dark:border-gray-600">
                                                    <span class="ml-2 text-sm">Show Related Posts</span>
                                                </label>
                                            </div>
                                        @endif

                                        @if($groupKey === 'appearance')
                                            <div>
                                                <label class="block text-sm font-medium mb-2">Theme Color</label>
                                                <input type="color" 
                                                       name="settings[theme_color]" 
                                                       value="{{ $groupSettings['theme_color'] ?? '#3b82f6' }}"
                                                       class="h-10 w-20 border border-gray-300 dark:border-gray-600 rounded-lg">
                                            </div>

                                            <div>
                                                <label class="flex items-center">
                                                    <input type="checkbox" 
                                                           name="settings[dark_mode_enabled]" 
                                                           value="1"
                                                           {{ ($groupSettings['dark_mode_enabled'] ?? '1') == '1' ? 'checked' : '' }}
                                                           class="rounded border-gray-300 dark:border-gray-600">
                                                    <span class="ml-2 text-sm">Enable Dark Mode</span>
                                                </label>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium mb-2">Footer Text</label>
                                                <textarea name="settings[footer_text]" 
                                                          rows="3"
                                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">{{ $groupSettings['footer_text'] ?? '' }}</textarea>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
                                        <button type="button" 
                                                @click="clearCache()"
                                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                                            Clear Cache
                                        </button>
                                        <button type="submit"
                                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                            Save Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endforeach

                    </div>

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function settingsManager() {
            return {
                activeTab: 'general',
                testEmail: '',

                async sendTestEmail() {
                    if (!this.testEmail) {
                        alert('Please enter an email address');
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("admin.settings.test-email") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ email: this.testEmail })
                        });

                        if (response.ok) {
                            alert('Test email sent successfully!');
                            this.testEmail = '';
                        } else {
                            alert('Failed to send test email');
                        }
                    } catch (error) {
                        console.error('Error sending test email:', error);
                        alert('Failed to send test email');
                    }
                },

                async clearCache() {
                    if (!confirm('Are you sure you want to clear the settings cache?')) {
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("admin.settings.clear-cache") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });

                        if (response.ok) {
                            alert('Settings cache cleared successfully!');
                        } else {
                            alert('Failed to clear cache');
                        }
                    } catch (error) {
                        console.error('Error clearing cache:', error);
                        alert('Failed to clear cache');
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
