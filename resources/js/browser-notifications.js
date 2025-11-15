/**
 * Browser Notifications Handler
 * 
 * Manages browser notification permissions and displays notifications
 * for new content when the user is not actively on the site.
 */

class BrowserNotifications {
    constructor() {
        this.permission = 'default';
        this.enabled = localStorage.getItem('browser_notifications_enabled') === 'true';
        
        if ('Notification' in window) {
            this.permission = Notification.permission;
        }
    }

    /**
     * Check if browser notifications are supported
     */
    isSupported() {
        return 'Notification' in window;
    }

    /**
     * Check if notifications are enabled
     */
    isEnabled() {
        return this.enabled && this.permission === 'granted';
    }

    /**
     * Request notification permission from the user
     */
    async requestPermission() {
        if (!this.isSupported()) {
            throw new Error('Browser notifications are not supported');
        }

        const permission = await Notification.requestPermission();
        this.permission = permission;

        if (permission === 'granted') {
            this.enabled = true;
            localStorage.setItem('browser_notifications_enabled', 'true');
            return true;
        }

        return false;
    }

    /**
     * Show a browser notification
     * 
     * @param {string} title - Notification title
     * @param {Object} options - Notification options
     * @param {string} options.body - Notification body text
     * @param {string} options.icon - Notification icon URL
     * @param {string} options.tag - Notification tag for grouping
     * @param {string} options.url - URL to navigate to when clicked
     * @param {Object} options.data - Additional data to attach
     */
    show(title, options = {}) {
        if (!this.isEnabled()) {
            console.log('Browser notifications are not enabled');
            return null;
        }

        const defaultOptions = {
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            vibrate: [200, 100, 200],
            requireInteraction: false,
            ...options
        };

        const notification = new Notification(title, defaultOptions);

        // Handle notification click
        if (options.url) {
            notification.onclick = (event) => {
                event.preventDefault();
                window.focus();
                window.location.href = options.url;
                notification.close();
            };
        }

        return notification;
    }

    /**
     * Show notification for new post
     */
    showNewPostNotification(post) {
        return this.show('New Article Published', {
            body: post.title,
            icon: post.featured_image || '/favicon.ico',
            tag: `post-${post.id}`,
            url: post.url,
            data: { type: 'new_post', post_id: post.id }
        });
    }

    /**
     * Show notification for comment reply
     */
    showCommentReplyNotification(comment) {
        return this.show('New Reply to Your Comment', {
            body: `${comment.author} replied: ${comment.excerpt}`,
            tag: `comment-${comment.id}`,
            url: comment.url,
            data: { type: 'comment_reply', comment_id: comment.id }
        });
    }

    /**
     * Show notification for comment approved
     */
    showCommentApprovedNotification(comment) {
        return this.show('Comment Approved', {
            body: `Your comment on "${comment.post_title}" has been approved`,
            tag: `comment-approved-${comment.id}`,
            url: comment.url,
            data: { type: 'comment_approved', comment_id: comment.id }
        });
    }

    /**
     * Show notification for series update
     */
    showSeriesUpdateNotification(series, post) {
        return this.show('Series Updated', {
            body: `New article added to "${series.title}"`,
            icon: post.featured_image || '/favicon.ico',
            tag: `series-${series.id}`,
            url: post.url,
            data: { type: 'series_update', series_id: series.id, post_id: post.id }
        });
    }

    /**
     * Disable browser notifications
     */
    disable() {
        this.enabled = false;
        localStorage.setItem('browser_notifications_enabled', 'false');
    }

    /**
     * Enable browser notifications (requires permission)
     */
    async enable() {
        if (!this.isSupported()) {
            throw new Error('Browser notifications are not supported');
        }

        if (this.permission !== 'granted') {
            const granted = await this.requestPermission();
            if (!granted) {
                throw new Error('Notification permission denied');
            }
        }

        this.enabled = true;
        localStorage.setItem('browser_notifications_enabled', 'true');
    }
}

// Create global instance
window.browserNotifications = new BrowserNotifications();

// Export for module usage
export default BrowserNotifications;
