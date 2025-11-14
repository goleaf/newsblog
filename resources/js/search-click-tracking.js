/**
 * Search Click Tracking
 * Tracks clicks on search results for analytics
 * Requirement: 16.2
 */

/**
 * Check if tracking is allowed (Requirement 16.4)
 */
function isTrackingAllowed() {
    // Check for Do Not Track header
    if (navigator.doNotTrack === '1' || window.doNotTrack === '1') {
        return false;
    }

    // Check for cookie consent
    const consent = getCookie('gdpr_consent');
    return consent === 'accepted';
}

/**
 * Get cookie value
 */
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

document.addEventListener('DOMContentLoaded', function () {
    // Only track if allowed
    if (!isTrackingAllowed()) {
        return;
    }

    // Track clicks on search result links
    const searchResults = document.querySelectorAll('[data-search-result]');

    searchResults.forEach((result) => {
        result.addEventListener('click', function (e) {
            const searchLogId = this.dataset.searchLogId;
            const postId = this.dataset.postId;
            const position = this.dataset.position;

            if (searchLogId && postId && position) {
                // Send tracking request (non-blocking)
                trackSearchClick(searchLogId, postId, position);
            }
        });
    });
});

/**
 * Track a search result click
 */
function trackSearchClick(searchLogId, postId, position) {
    // Use sendBeacon for non-blocking tracking
    if (navigator.sendBeacon) {
        const formData = new FormData();
        formData.append('search_log_id', searchLogId);
        formData.append('post_id', postId);
        formData.append('position', position);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

        navigator.sendBeacon('/search/track-click', formData);
    } else {
        // Fallback to fetch with keepalive
        fetch('/search/track-click', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                search_log_id: searchLogId,
                post_id: postId,
                position: position,
            }),
            keepalive: true,
        }).catch(() => {
            // Silently fail - don't block user navigation
        });
    }
}
