/**
 * Browser Notifications Handler
 * Manages browser push notifications for the application
 */

class BrowserNotifications {
    constructor() {
        this.permission = Notification.permission;
        this.enabled = localStorage.getItem('browser_notifications_enabled') === 'true';
    }

    /**
     * Check if browser notifications are supported
     */
    isSupported() {
        return 'Notification' in window;
    }

    /**
     * Request permission for browser notifications
     */
    async requestPermission() {
        if (!this.isSupported()) {
            console.warn('Browser notifications are not supported');
            return false;
        }

        if (this.permission === 'granted') {
            this.enabled = true;
            localStorage.setItem('browser_notifications_enabled', 'true');
            return true;
        }

        if (this.permission === 'denied') {
            console.warn('Browser notifications permission denied');
            return false;
        }

        try {
            const permission = await Notification.requestPermission();
            this.permission = permission;
            
            if (permission === 'granted') {
                this.enabled = true;
                localStorage.setItem('browser_notifications_enabled', 'true');
                
                // Show a test notification
                this.show('Notifications Enabled', {
                    body: 'You will now receive notifications from TechNewsHub',
                    icon: '/favicon.ico',
                });
                
                return true;
            }
            
            return false;
        } catch (error) {
            console.error('Error requesting notification permission:', error);
            return false;
        }
    }

    /**
     * Show a browser notification
     */
    show(title, options = {}) {
        if (!this.isSupported() || !this.enabled || this.permission !== 'granted') {
            return null;
        }

        const defaultOptions = {
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            vibrate: [200, 100, 200],
            requireInteraction: false,
            ...options
        };

        try {
            const notification = new Notification(title, defaultOptions);
            
            // Handle notification click
            notification.onclick = (event) => {
                event.preventDefault();
                window.focus();
                
                if (options.url) {
                    window.location.href = options.url;
                }
                
                notification.close();
            };
            
            return notification;
        } catch (error) {
            console.error('Error showing notification:', error);
            return null;
        }
    }

    /**
     * Disable browser notifications
     */
    disable() {
        this.enabled = false;
        localStorage.setItem('browser_notifications_enabled', 'false');
    }

    /**
     * Enable browser notifications
     */
    async enable() {
        return await this.requestPermission();
    }

    /**
     * Check if notifications are enabled
     */
    isEnabled() {
        return this.enabled && this.permission === 'granted';
    }

    /**
     * Poll for new notifications
     */
    async pollNotifications() {
        if (!this.isEnabled()) {
            return;
        }

        try {
            const response = await fetch('/notifications/unread', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.notifications.length > 0) {
                // Show only the most recent unread notification
                const notification = data.notifications[0];
                this.show(notification.title, {
                    body: notification.message,
                    url: notification.action_url,
                    tag: `notification-${notification.id}`,
                });
            }
        } catch (error) {
            console.error('Error polling notifications:', error);
        }
    }

    /**
     * Start polling for notifications
     */
    startPolling(interval = 60000) { // Default: 1 minute
        if (!this.isEnabled()) {
            return;
        }

        // Poll immediately
        this.pollNotifications();

        // Then poll at intervals
        this.pollingInterval = setInterval(() => {
            this.pollNotifications();
        }, interval);
    }

    /**
     * Stop polling for notifications
     */
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }
}

// Export for use in other modules
export default BrowserNotifications;

// Initialize globally if not using modules
if (typeof window !== 'undefined') {
    window.BrowserNotifications = BrowserNotifications;
}
