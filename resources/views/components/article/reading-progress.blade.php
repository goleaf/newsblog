@props(['articleId' => 'article-content'])

<div 
    x-data="readingProgress({ articleId: '{{ $articleId }}' })" 
    x-init="init()"
    class="fixed top-0 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 z-50 print:hidden"
    role="progressbar"
    :aria-valuenow="progress"
    aria-valuemin="0"
    aria-valuemax="100"
    aria-label="Reading progress"
>
    <div 
        class="h-full bg-indigo-600 dark:bg-indigo-500 transition-all duration-100 ease-out"
        :style="`width: ${progress}%`"
    ></div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('readingProgress', (config) => ({
        progress: 0,
        articleElement: null,
        
        init() {
            this.articleElement = document.getElementById(config.articleId);
            
            if (!this.articleElement) {
                console.warn(`Article element with id "${config.articleId}" not found`);
                return;
            }
            
            // Calculate progress on scroll
            this.calculateProgress();
            
            // Update on scroll with throttling
            let ticking = false;
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        this.calculateProgress();
                        ticking = false;
                    });
                    ticking = true;
                }
            });
            
            // Recalculate on window resize
            window.addEventListener('resize', () => {
                this.calculateProgress();
            });
        },
        
        calculateProgress() {
            if (!this.articleElement) return;
            
            const articleTop = this.articleElement.offsetTop;
            const articleHeight = this.articleElement.offsetHeight;
            const windowHeight = window.innerHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Calculate how much of the article has been scrolled through
            const articleBottom = articleTop + articleHeight;
            const scrollProgress = scrollTop + windowHeight - articleTop;
            
            // Calculate percentage (0-100)
            let percentage = 0;
            
            if (scrollTop < articleTop) {
                // Before article
                percentage = 0;
            } else if (scrollTop + windowHeight > articleBottom) {
                // Past article
                percentage = 100;
            } else {
                // Within article
                percentage = (scrollProgress / articleHeight) * 100;
            }
            
            // Clamp between 0 and 100
            this.progress = Math.max(0, Math.min(100, percentage));
        }
    }));
});
</script>
@endpush
