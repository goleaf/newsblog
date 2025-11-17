{{--
    Mobile Reading Controls Component
    
    Provides mobile-optimized reading controls including font size adjustment,
    reading progress, and other reading enhancements.
    Requirements: 17.3, 17.4
--}}

@props([
    'article' => null,
])

<div 
    x-data="mobileReadingControls"
    class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 shadow-lg transition-transform duration-300"
    :class="visible ? 'translate-y-0' : 'translate-y-full'"
>
    {{-- Reading Progress Bar --}}
    <div class="h-1 bg-gray-200 dark:bg-gray-700">
        <div 
            class="h-full bg-blue-600 dark:bg-blue-500 transition-all duration-300"
            :style="`width: ${readingProgress}%`"
            role="progressbar"
            :aria-valuenow="readingProgress"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label="Reading progress"
        ></div>
    </div>
    
    {{-- Control Bar --}}
    <div class="flex items-center justify-between px-4 py-3">
        {{-- Font Size Controls --}}
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400 mr-2">Font Size</span>
            
            <button 
                @click="decreaseFontSize"
                type="button"
                class="p-2 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors touch-target"
                aria-label="Decrease font size"
                :disabled="fontSize <= minFontSize"
                :class="fontSize <= minFontSize ? 'opacity-50 cursor-not-allowed' : ''"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                </svg>
                <span class="ml-1 text-sm font-medium">A</span>
            </button>
            
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 min-w-[3rem] text-center" x-text="fontSize + 'px'"></span>
            
            <button 
                @click="increaseFontSize"
                type="button"
                class="p-2 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors touch-target"
                aria-label="Increase font size"
                :disabled="fontSize >= maxFontSize"
                :class="fontSize >= maxFontSize ? 'opacity-50 cursor-not-allowed' : ''"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="ml-1 text-lg font-medium">A</span>
            </button>
        </div>
        
        {{-- Reading Time & Progress --}}
        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
            @if($article && $article->reading_time)
                <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-text="estimatedTimeLeft"></span>
                </span>
            @endif
            
            <span class="font-medium" x-text="Math.round(readingProgress) + '%'"></span>
        </div>
        
        {{-- Toggle Controls Visibility --}}
        <button 
            @click="visible = !visible"
            type="button"
            class="p-2 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors touch-target"
            aria-label="Hide reading controls"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </div>
</div>

{{-- Show Controls Button (when hidden) --}}
<button 
    x-show="!visible"
    @click="visible = true"
    type="button"
    class="lg:hidden fixed bottom-4 right-4 z-40 p-3 min-w-[56px] min-h-[56px] flex items-center justify-center bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-all touch-target"
    aria-label="Show reading controls"
>
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
    </svg>
</button>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('mobileReadingControls', () => ({
        visible: true,
        readingProgress: 0,
        fontSize: 16,
        minFontSize: 14,
        maxFontSize: 22,
        totalReadingTime: {{ $article->reading_time ?? 5 }}, // in minutes
        
        init() {
            // Load saved font size preference
            const savedFontSize = localStorage.getItem('reading-font-size');
            if (savedFontSize) {
                this.fontSize = parseInt(savedFontSize);
                this.applyFontSize();
            }
            
            // Track reading progress
            this.trackReadingProgress();
            
            // Auto-hide controls after inactivity
            this.setupAutoHide();
        },
        
        trackReadingProgress() {
            let ticking = false;
            
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        this.updateReadingProgress();
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });
            
            // Initial calculation
            this.updateReadingProgress();
        },
        
        updateReadingProgress() {
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Calculate progress
            const scrollableHeight = documentHeight - windowHeight;
            this.readingProgress = scrollableHeight > 0 
                ? (scrollTop / scrollableHeight) * 100 
                : 0;
        },
        
        get estimatedTimeLeft() {
            const remainingPercent = 100 - this.readingProgress;
            const remainingMinutes = Math.ceil((this.totalReadingTime * remainingPercent) / 100);
            
            if (remainingMinutes < 1) {
                return 'Less than 1 min';
            } else if (remainingMinutes === 1) {
                return '1 min left';
            } else {
                return `${remainingMinutes} min left`;
            }
        },
        
        increaseFontSize() {
            if (this.fontSize < this.maxFontSize) {
                this.fontSize += 2;
                this.applyFontSize();
                this.saveFontSize();
            }
        },
        
        decreaseFontSize() {
            if (this.fontSize > this.minFontSize) {
                this.fontSize -= 2;
                this.applyFontSize();
                this.saveFontSize();
            }
        },
        
        applyFontSize() {
            const articleContent = document.querySelector('.article-content, .prose');
            if (articleContent) {
                articleContent.style.fontSize = `${this.fontSize}px`;
            }
        },
        
        saveFontSize() {
            localStorage.setItem('reading-font-size', this.fontSize);
        },
        
        setupAutoHide() {
            let hideTimeout;
            
            const resetHideTimer = () => {
                clearTimeout(hideTimeout);
                this.visible = true;
                
                hideTimeout = setTimeout(() => {
                    if (window.scrollY > 200) {
                        this.visible = false;
                    }
                }, 3000); // Hide after 3 seconds of inactivity
            };
            
            // Show controls on scroll or touch
            window.addEventListener('scroll', resetHideTimer, { passive: true });
            window.addEventListener('touchstart', resetHideTimer, { passive: true });
        }
    }));
});
</script>
