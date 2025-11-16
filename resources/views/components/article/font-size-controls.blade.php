@props(['target' => '#article-content'])

<div 
    x-data="fontSizeControls({
        target: @js($target),
        minSize: 80,
        maxSize: 150,
        step: 10,
        defaultSize: 100,
        storageKey: 'article-font-size'
    })"
    class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
>
    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">
        {{ __('post.font_size') }}:
    </span>

    {{-- Decrease Button --}}
    <button
        @click="decrease"
        :disabled="fontSize <= minSize"
        class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        :aria-label="__('post.decrease_font_size')"
        title="{{ __('post.decrease_font_size') }}"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
        </svg>
    </button>

    {{-- Current Size Display --}}
    <span 
        class="inline-flex items-center justify-center min-w-[3rem] px-2 py-1 text-sm font-semibold text-gray-900 dark:text-white"
        x-text="fontSize + '%'"
    ></span>

    {{-- Increase Button --}}
    <button
        @click="increase"
        :disabled="fontSize >= maxSize"
        class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        :aria-label="__('post.increase_font_size')"
        title="{{ __('post.increase_font_size') }}"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
    </button>

    {{-- Reset Button --}}
    <button
        @click="reset"
        :disabled="fontSize === defaultSize"
        class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        :aria-label="__('post.reset_font_size')"
        title="{{ __('post.reset_font_size') }}"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>{{ __('post.reset') }}</span>
    </button>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('fontSizeControls', (config) => ({
        fontSize: config.defaultSize,
        minSize: config.minSize,
        maxSize: config.maxSize,
        step: config.step,
        defaultSize: config.defaultSize,
        storageKey: config.storageKey,
        target: config.target,

        init() {
            // Load saved font size from localStorage
            const saved = localStorage.getItem(this.storageKey);
            if (saved) {
                const parsed = parseInt(saved, 10);
                if (!isNaN(parsed) && parsed >= this.minSize && parsed <= this.maxSize) {
                    this.fontSize = parsed;
                }
            }

            // Apply initial font size
            this.applyFontSize();

            // Watch for changes and save to localStorage
            this.$watch('fontSize', (value) => {
                this.applyFontSize();
                localStorage.setItem(this.storageKey, value.toString());
            });
        },

        applyFontSize() {
            const element = document.querySelector(this.target);
            if (element) {
                // Apply font size to article content
                const contentElement = element.querySelector('.prose') || element;
                contentElement.style.fontSize = `${this.fontSize}%`;
            }
        },

        increase() {
            if (this.fontSize < this.maxSize) {
                this.fontSize = Math.min(this.fontSize + this.step, this.maxSize);
            }
        },

        decrease() {
            if (this.fontSize > this.minSize) {
                this.fontSize = Math.max(this.fontSize - this.step, this.minSize);
            }
        },

        reset() {
            this.fontSize = this.defaultSize;
        }
    }));
});
</script>
@endpush

