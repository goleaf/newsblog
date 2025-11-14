const getScrollTop = () =>
    window.pageYOffset || document.documentElement.scrollTop || 0;

export default function readingProgress({ articleId = 'article-content' } = {}) {
    return {
        progress: 0,

        init() {
            this.calculateProgress = this.calculateProgress.bind(this);

            window.addEventListener('scroll', this.calculateProgress, {
                passive: true,
            });

            window.addEventListener('resize', this.calculateProgress);

            this.calculateProgress();
        },

        destroy() {
            window.removeEventListener('scroll', this.calculateProgress);
            window.removeEventListener('resize', this.calculateProgress);
        },

        calculateProgress() {
            const article = document.getElementById(articleId);

            if (!article) {
                this.progress = 0;

                return;
            }

            const articleTop = article.offsetTop;
            const articleHeight = article.offsetHeight;
            const windowHeight = window.innerHeight;
            const scrollTop = getScrollTop();

            const scrolled = scrollTop - articleTop;
            const total = articleHeight - windowHeight;

            if (total <= 0) {
                this.progress = 0;

                return;
            }

            const percentage = (scrolled / total) * 100;

            this.progress = Math.min(100, Math.max(0, Math.round(percentage)));
        },
    };
}

