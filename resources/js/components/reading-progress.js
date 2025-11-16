/**
 * Reading Progress Indicator Component
 * Requirement 21: Display reading progress bar at top of page
 * Calculates progress based on scroll position with smooth transitions
 */

const getScrollTop = () => {
    return window.pageYOffset || document.documentElement.scrollTop || 0;
};

const getWindowHeight = () => {
    return window.innerHeight || document.documentElement.clientHeight || 0;
};

export default function readingProgress({ articleId = 'article-content' } = {}) {
    return {
        progress: 0,
        articleElement: null,

        init() {
            this.articleElement = document.getElementById(articleId);

            if (!this.articleElement) {
                console.warn(`Reading progress: Article element with id "${articleId}" not found`);
                return;
            }

            // Bind calculateProgress for event listeners
            this.calculateProgress = this.calculateProgress.bind(this);

            // Calculate initial progress
            this.calculateProgress();

            // Update on scroll with throttling using requestAnimationFrame (Requirement 21.4)
            let ticking = false;
            window.addEventListener(
                'scroll',
                () => {
                    if (!ticking) {
                        window.requestAnimationFrame(() => {
                            this.calculateProgress();
                            ticking = false;
                        });
                        ticking = true;
                    }
                },
                { passive: true }
            );

            // Recalculate on window resize
            window.addEventListener('resize', this.calculateProgress, { passive: true });
        },

        destroy() {
            window.removeEventListener('scroll', this.calculateProgress);
            window.removeEventListener('resize', this.calculateProgress);
        },

        /**
         * Calculate reading progress based on scroll position
         * Requirement 21.3: Calculate based on article content height excluding header and footer
         * Requirement 21.2: Show 100% at end of article
         */
        calculateProgress() {
            if (!this.articleElement) {
                this.progress = 0;
                return;
            }

            const articleTop = this.articleElement.offsetTop;
            const articleHeight = this.articleElement.offsetHeight;
            const windowHeight = getWindowHeight();
            const scrollTop = getScrollTop();

            // Calculate article boundaries
            const articleBottom = articleTop + articleHeight;

            // Calculate how much of the article has been scrolled through
            // Progress starts when article enters viewport and completes when bottom is reached
            let percentage = 0;

            if (scrollTop < articleTop) {
                // Before article starts (Requirement 21.2)
                percentage = 0;
            } else if (scrollTop + windowHeight >= articleBottom) {
                // Past article end - show 100% completion (Requirement 21.2)
                percentage = 100;
            } else {
                // Within article - calculate percentage based on scroll position
                // Formula: (scroll position - article start) / (article height - viewport height)
                const scrolled = scrollTop - articleTop;
                const totalScrollable = articleHeight - windowHeight;

                if (totalScrollable <= 0) {
                    // Article is shorter than viewport
                    percentage = 100;
                } else {
                    percentage = (scrolled / totalScrollable) * 100;
                }
            }

            // Clamp between 0 and 100 and round for smooth display
            // Requirement 21.4: Smooth transitions handled by CSS transition-all duration-100
            this.progress = Math.min(100, Math.max(0, Math.round(percentage)));
        },
    };
}

