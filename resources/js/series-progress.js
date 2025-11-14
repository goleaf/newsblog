/**
 * Series Progress Tracking
 * 
 * Tracks user's reading progress through article series using localStorage.
 * Automatically marks articles as read when user visits them.
 */

export function seriesProgress(seriesId, postIds) {
    return {
        seriesId: seriesId,
        allPostIds: postIds,
        readPosts: [],
        completionPercentage: 0,
        
        init() {
            this.loadProgress();
            this.calculateCompletion();
            
            // Auto-mark current post as read if on a post page
            this.autoMarkCurrentPost();
        },
        
        /**
         * Load progress from localStorage
         */
        loadProgress() {
            const key = `series_progress_${this.seriesId}`;
            const stored = localStorage.getItem(key);
            this.readPosts = stored ? JSON.parse(stored) : [];
        },
        
        /**
         * Save progress to localStorage
         */
        saveProgress() {
            const key = `series_progress_${this.seriesId}`;
            localStorage.setItem(key, JSON.stringify(this.readPosts));
            this.calculateCompletion();
            
            // Dispatch event for other components
            window.dispatchEvent(new CustomEvent('series-progress-updated', {
                detail: {
                    seriesId: this.seriesId,
                    readPosts: this.readPosts,
                    completionPercentage: this.completionPercentage
                }
            }));
        },
        
        /**
         * Toggle read status for a post
         */
        toggleRead(postId) {
            const index = this.readPosts.indexOf(postId);
            if (index > -1) {
                this.readPosts.splice(index, 1);
            } else {
                this.readPosts.push(postId);
            }
            this.saveProgress();
        },
        
        /**
         * Mark a post as read
         */
        markAsRead(postId) {
            if (!this.readPosts.includes(postId)) {
                this.readPosts.push(postId);
                this.saveProgress();
            }
        },
        
        /**
         * Mark a post as unread
         */
        markAsUnread(postId) {
            const index = this.readPosts.indexOf(postId);
            if (index > -1) {
                this.readPosts.splice(index, 1);
                this.saveProgress();
            }
        },
        
        /**
         * Check if a post is read
         */
        isRead(postId) {
            return this.readPosts.includes(postId);
        },
        
        /**
         * Calculate completion percentage
         */
        calculateCompletion() {
            const total = this.allPostIds.length;
            const read = this.readPosts.length;
            this.completionPercentage = total > 0 ? Math.round((read / total) * 100) : 0;
        },
        
        /**
         * Check if series is completed
         */
        isCompleted() {
            return this.completionPercentage === 100;
        },
        
        /**
         * Get next unread post ID
         */
        getNextUnreadPost() {
            for (const postId of this.allPostIds) {
                if (!this.readPosts.includes(postId)) {
                    return postId;
                }
            }
            return null;
        },
        
        /**
         * Auto-mark current post as read (when viewing a post page)
         */
        autoMarkCurrentPost() {
            // Check if we're on a post page with a series
            const postIdMeta = document.querySelector('meta[name="post-id"]');
            if (postIdMeta) {
                const currentPostId = parseInt(postIdMeta.content);
                if (this.allPostIds.includes(currentPostId)) {
                    // Mark as read after 30 seconds of viewing
                    setTimeout(() => {
                        this.markAsRead(currentPostId);
                    }, 30000);
                }
            }
        },
        
        /**
         * Reset progress for this series
         */
        resetProgress() {
            if (confirm('Are you sure you want to reset your progress for this series?')) {
                this.readPosts = [];
                this.saveProgress();
            }
        },
        
        /**
         * Get progress summary
         */
        getProgressSummary() {
            return {
                total: this.allPostIds.length,
                read: this.readPosts.length,
                remaining: this.allPostIds.length - this.readPosts.length,
                percentage: this.completionPercentage,
                isCompleted: this.isCompleted()
            };
        }
    };
}

// Make it globally available
window.seriesProgress = seriesProgress;
