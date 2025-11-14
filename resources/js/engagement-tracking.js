/**
 * Engagement Metrics Tracking
 * Tracks time on page, scroll depth, and user interactions
 * Requirement: 16.3
 */

class EngagementTracker {
    constructor(postId) {
        this.postId = postId;
        this.startTime = Date.now();
        this.maxScrollDepth = 0;
        this.interactions = {
            clicked_bookmark: false,
            clicked_share: false,
            clicked_reaction: false,
            clicked_comment: false,
            clicked_related_post: false,
        };
        this.trackingInterval = null;
        this.hasTracked = false;

        // Check if tracking is allowed (Requirement 16.4)
        if (this.isTrackingAllowed()) {
            this.init();
        }
    }

    isTrackingAllowed() {
        // Check for Do Not Track header
        if (navigator.doNotTrack === '1' || window.doNotTrack === '1') {
            return false;
        }

        // Check for cookie consent
        const consent = this.getCookie('gdpr_consent');
        return consent === 'accepted';
    }

    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    init() {
        // Track scroll depth
        this.trackScrollDepth();
        window.addEventListener('scroll', () => this.trackScrollDepth());

        // Track interactions
        this.trackInteractions();

        // Send metrics periodically (every 30 seconds)
        this.trackingInterval = setInterval(() => this.sendMetrics(), 30000);

        // Send metrics before page unload
        window.addEventListener('beforeunload', () => this.sendMetrics(true));

        // Send metrics when page becomes hidden (mobile)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.sendMetrics(true);
            }
        });
    }

    trackScrollDepth() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollDepth = Math.round(((scrollTop + windowHeight) / documentHeight) * 100);

        if (scrollDepth > this.maxScrollDepth) {
            this.maxScrollDepth = Math.min(scrollDepth, 100);
        }
    }

    trackInteractions() {
        // Track bookmark clicks
        document.querySelectorAll('[data-bookmark-button]').forEach((button) => {
            button.addEventListener('click', () => {
                this.interactions.clicked_bookmark = true;
                this.sendMetrics();
            });
        });

        // Track share clicks
        document.querySelectorAll('[data-share-button]').forEach((button) => {
            button.addEventListener('click', () => {
                this.interactions.clicked_share = true;
                this.sendMetrics();
            });
        });

        // Track reaction clicks
        document.querySelectorAll('[data-reaction-button]').forEach((button) => {
            button.addEventListener('click', () => {
                this.interactions.clicked_reaction = true;
                this.sendMetrics();
            });
        });

        // Track comment clicks
        document.querySelectorAll('[data-comment-button], [data-comment-form]').forEach((element) => {
            element.addEventListener('click', () => {
                this.interactions.clicked_comment = true;
                this.sendMetrics();
            });
        });

        // Track related post clicks
        document.querySelectorAll('[data-related-post]').forEach((link) => {
            link.addEventListener('click', () => {
                this.interactions.clicked_related_post = true;
                this.sendMetrics(true);
            });
        });
    }

    getTimeOnPage() {
        return Math.round((Date.now() - this.startTime) / 1000);
    }

    sendMetrics(useBeacon = false) {
        const timeOnPage = this.getTimeOnPage();

        // Don't send if no meaningful engagement (less than 5 seconds)
        if (timeOnPage < 5 && !this.hasTracked) {
            return;
        }

        const data = {
            post_id: this.postId,
            time_on_page: timeOnPage,
            scroll_depth: this.maxScrollDepth,
            ...this.interactions,
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        if (useBeacon && navigator.sendBeacon) {
            // Use sendBeacon for non-blocking tracking on page unload
            const formData = new FormData();
            Object.keys(data).forEach((key) => {
                formData.append(key, data[key]);
            });
            formData.append('_token', csrfToken);

            navigator.sendBeacon('/engagement/track', formData);
        } else {
            // Use fetch for periodic updates
            fetch('/engagement/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(data),
                keepalive: true,
            }).catch(() => {
                // Silently fail - don't block user experience
            });
        }

        this.hasTracked = true;
    }

    destroy() {
        if (this.trackingInterval) {
            clearInterval(this.trackingInterval);
        }
        this.sendMetrics(true);
    }
}

// Initialize engagement tracking on article pages
document.addEventListener('DOMContentLoaded', function () {
    const postElement = document.querySelector('[data-post-id]');
    if (postElement) {
        const postId = postElement.dataset.postId;
        if (postId) {
            window.engagementTracker = new EngagementTracker(postId);
        }
    }
});
