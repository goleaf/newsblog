@props([
    'user' => auth()->user(),
])

<div 
    x-data="{
        browserNotificationsEnabled: window.browserNotifications?.isEnabled() || false,
        permission: window.browserNotifications?.permission || 'default',
        
        init() {
            if (window.browserNotifications) {
                this.permission = window.browserNotifications.permission;
                this.browserNotificationsEnabled = window.browserNotifications.isEnabled();
            }
        },
        
        async toggleBrowserNotifications() {
            if (!window.browserNotifications || !window.browserNotifications.isSupported()) {
                alert('Browser notifications are not supported in your browser');
                return;
            }
            
            if (this.browserNotificationsEnabled) {
                // Disable
                window.browserNotifications.disable();
                this.browserNotificationsEnabled = false;
            } else {
                // Enable - request permission
                try {
                    await window.browserNotifications.enable();
                    this.browserNotificationsEnabled = true;
                    this.permission = window.browserNotifications.permission;
                    
                    // Show test notification
                    window.browserNotifications.show('Notifications Enabled', {
                        body: 'You will now receive browser notifications',
                        icon: '/favicon.ico'
                    });
                } catch (error) {
                    alert(error.message || 'Please allow notifications in your browser settings');
                }
            }
        }
    }"
    class="bg-white dark:bg-gray-800 rounded-lg shadow p-6"
>
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Notification Settings
    </h3>
    
    <div class="space-y-4">
        <!-- Browser Notifications -->
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <label for="browser-notifications" class="text-sm font-medium text-gray-900 dark:text-white">
                    Browser Notifications
                </label>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    Receive notifications in your browser when you're not on the site
                </p>
            </div>
            <button 
                @click="toggleBrowserNotifications()"
                type="button"
                role="switch"
                :aria-checked="browserNotificationsEnabled"
                :class="browserNotificationsEnabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                <span 
                    :class="browserNotificationsEnabled ? 'translate-x-5' : 'translate-x-0'"
                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                ></span>
            </button>
        </div>
        
        <!-- Permission Status -->
        <div x-show="permission === 'denied'" class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                <strong>Note:</strong> Browser notifications are blocked. Please enable them in your browser settings.
            </p>
        </div>
    </div>
</div>
