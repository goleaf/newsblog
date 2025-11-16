{{--
    Scroll to Top Button Component
    
    A fixed button in the bottom-right corner that appears after scrolling 300px.
    Implements smooth scroll to top with fade in/out animations.
    
    Usage:
    <x-ui.scroll-to-top />
    
    Requirements: 74
--}}

<button
    x-data="scrollToTop"
    x-init="init()"
    @click="scrollToTop()"
    x-show="isVisible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-90"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-90"
    x-cloak
    class="fixed bottom-6 right-6 z-50 w-12 h-12 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
    aria-label="Scroll to top"
    title="Scroll to top"
>
    <svg 
        class="w-6 h-6" 
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
        aria-hidden="true"
    >
        <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M5 10l7-7m0 0l7 7m-7-7v18" 
        />
    </svg>
</button>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('scrollToTop', () => ({
        isVisible: false,
        scrollThreshold: 300,
        
        init() {
            // Check initial scroll position
            this.checkScrollPosition();
            
            // Listen for scroll events with throttling
            let ticking = false;
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        this.checkScrollPosition();
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });
        },
        
        checkScrollPosition() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            this.isVisible = scrollTop > this.scrollThreshold;
        },
        
        scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    }));
});
</script>

